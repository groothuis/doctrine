<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Console\Command;

use Symfony\Components\Console\Input\InputArgument,
    Symfony\Components\Console\Input\InputOption,
    Symfony\Components\Console,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter,
    Doctrine\ORM\Tools\ConvertDoctrine1Schema;

/**
 * Command to convert a Doctrine 1 schema to a Doctrine 2 mapping file.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ConvertDoctrine1SchemaCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('orm:convert-d1-schema')
        ->setDescription('Converts Doctrine 1.X schema into a Doctrine 2.X schema.')
        ->setDefinition(array(
            new InputArgument(
                'from-path', InputArgument::REQUIRED, 'The path of Doctrine 1.X schema information.'
            ),
            new InputArgument(
                'to-type', InputArgument::REQUIRED, 'The destination Doctrine 2.X mapping type.'
            ),
            new InputArgument(
                'dest-path', InputArgument::REQUIRED,
                'The path to generate your Doctrine 2.X mapping information.'
            ),
            new InputOption(
                'from', null, InputOption::PARAMETER_REQUIRED | InputOption::PARAMETER_IS_ARRAY,
                'Optional paths of Doctrine 1.X schema information.',
                array()
            ),
            new InputOption(
                'extend', null, InputOption::PARAMETER_OPTIONAL,
                'Defines a base class to be extended by generated entity classes.'
            ),
            new InputOption(
                'num-spaces', null, InputOption::PARAMETER_OPTIONAL,
                'Defines the number of indentation spaces', 4
            )
        ))
        ->setHelp(<<<EOT
Converts Doctrine 1.X schema into a Doctrine 2.X schema.
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $em = $this->getHelper('em')->getEntityManager();

        // Process source directories
        $fromPaths = array_merge(array($input->getArgument('from-path')), $input->getOption('from'));

        foreach ($fromPaths as &$dirName) {
            $dirName = realpath($dirName);

            if ( ! file_exists($dirName)) {
                throw new \InvalidArgumentException(
                    sprintf("Doctrine 1.X schema directory '<info>%s</info>' does not exist.", $dirName)
                );
            } else if ( ! is_readable($dirName)) {
                throw new \InvalidArgumentException(
                    sprintf("Doctrine 1.X schema directory '<info>%s</info>' does not have read permissions.", $dirName)
                );
            }
        }

        // Process destination directory
        $destPath = realpath($input->getArgument('dest-path'));

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Doctrine 2.X mapping destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        } else if ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Doctrine 2.X mapping destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        $toType = $input->getArgument('to-type');

        $cme = new ClassMetadataExporter();
        $exporter = $cme->getExporter($toType, $destPath);

        if (strtolower($toType) === 'annotation') {
            $entityGenerator = new EntityGenerator();
            $exporter->setEntityGenerator($entityGenerator);

            $entityGenerator->setNumSpaces($input->getOption('num-spaces'));

            if (($extend = $input->getOption('extend')) !== null) {
                $entityGenerator->setClassToExtend($extend);
            }
        }

        $converter = new ConvertDoctrine1Schema($fromPaths);
        $metadata = $converter->getMetadata();

        if ($metadata) {
            $output->write(PHP_EOL);

            foreach ($metadata as $class) {
                $output->write(sprintf('Processing entity "<info>%s</info>"', $class->name) . PHP_EOL);
            }

            $exporter->setMetadata($metadata);
            $exporter->export();

            $output->write(PHP_EOL . sprintf(
                'Converting Doctrine 1.X schema to "<info>%s</info>" mapping type in "<info>%s</info>"', $toType, $destPath
            ));
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }
    }
}