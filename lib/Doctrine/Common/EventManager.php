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

namespace Doctrine\Common;

use Doctrine\Common\Events\Event;

/**
 * The EventManager is the central point of Doctrine's event listener system.
 * Listeners are registered on the manager and events are dispatched through the
 * manager.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @since 2.0
 */
class EventManager
{
    /**
     * Map of registered listeners.
     * <event> => <listeners> 
     *
     * @var array
     */
    private $_listeners = array();

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string $eventName  The name of the event to dispatch. The name of the event is
     *                           the name of the method that is invoked on listeners.
     * @param EventArgs $eventArgs The event arguments to pass to the event handlers/listeners.
     *                             If not supplied, the single empty EventArgs instance is used.
     * @return boolean
     */
    public function dispatchEvent($eventName, EventArgs $eventArgs = null)
    {
        if (isset($this->_listeners[$eventName])) {
            $eventArgs = $eventArgs === null ? EventArgs::getEmptyInstance() : $eventArgs;
            foreach ($this->_listeners[$eventName] as $listener) {
                $listener->$eventName($eventArgs);
            }
        }
    }

    /**
     * Gets the listeners of a specific event or all listeners.
     *
     * @param string $event  The name of the event.
     * @return The event listeners for the specified event, or all event listeners.
     */
    public function getListeners($event = null)
    {
        return $event ? $this->_listeners[$event] : $this->_listeners;
    }

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string $event
     * @return boolean TRUE if the specified event has any listeners, FALSE otherwise.
     */
    public function hasListeners($event)
    {
        return isset($this->_listeners[$event]);
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string|array $events  The event(s) to listen on.
     * @param object $listener  The listener object.
     */
    public function addEventListener($events, $listener)
    {
        // TODO: maybe check for duplicate registrations?
        foreach ((array)$events as $event) {
            $this->_listeners[$event][] = $listener;
        }
    }
    
    /**
     * Adds an EventSubscriber. The subscriber is asked for all the events he is
     * interested in and added as a listener for these events.
     * 
     * @param Doctrine\Common\EventSubscriber $subscriber  The subscriber.
     */
    public function addEventSubscriber(EventSubscriber $subscriber)
    {
        $this->addEventListener($subscriber->getSubscribedEvents(), $subscriber);
    }
}