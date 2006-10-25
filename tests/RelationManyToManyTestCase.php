<?php
class M2MTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('child_id', 'integer');
    }
    public function setUp() {
        $this->ownsMany('OwnsOneToManyWithAlias as AliasO2M', 'AliasO2M.component_id');
        $this->hasMany('RTC1 as RTC1', 'JC1.c1_id');
        $this->hasMany('RTC2 as RTC2', 'JC1.c1_id');
        $this->hasMany('RTC3 as RTC3', 'JC2.c1_id');
        $this->hasMany('RTC3 as RTC4', 'JC1.c1_id');
    }
}

class JC1 extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('c1_id', 'integer');
        $this->hasColumn('c2_id', 'integer');
    }
}
class JC2 extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('c1_id', 'integer');
        $this->hasColumn('c2_id', 'integer');
    }
}
class RTC1 extends Doctrine_Record {
    public function setTableDefinition() { 
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp() {
        $this->hasMany('M2MTest as RTC1', 'JC1.c2_id');
    }
}
class RTC2 extends Doctrine_Record {
    public function setTableDefinition() { 
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp() {
        $this->hasMany('M2MTest as RTC2', 'JC1.c2_id');
    }
}
class RTC3 extends Doctrine_Record {
    public function setTableDefinition() { 
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp() {
        $this->hasMany('M2MTest as RTC3', 'JC2.c2_id');
        $this->hasMany('M2MTest as RTC4', 'JC1.c2_id');
    }
}

class Doctrine_Relation_ManyToMany_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        parent::prepareTables();
    }
    public function testManyToManyHasRelationWithAliases4() {
        $component = new M2MTest();

        try {
            $rel = $component->getTable()->getRelation('RTC4');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_Association);
        
        $this->assertTrue($component->RTC4 instanceof Doctrine_Collection);
    }

    public function testManyToManyHasRelationWithAliases3() {
        $component = new M2MTest();

        try {
            $rel = $component->getTable()->getRelation('RTC3');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_Association);
        
        $this->assertTrue($component->RTC3 instanceof Doctrine_Collection);
    }


    public function testManyToManyHasRelationWithAliases() {
        $component = new M2MTest();
        $component->AliasO2M;
        
        try {
            $rel = $component->getTable()->getRelation('RTC1');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_Association);
        
        $this->assertTrue($component->RTC1 instanceof Doctrine_Collection);
    }

    public function testManyToManyHasRelationWithAliases2() {
        $component = new M2MTest();
        
        try {
            $rel = $component->getTable()->getRelation('RTC2');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_Association);
        
        $this->assertTrue($component->RTC1 instanceof Doctrine_Collection);
    }


    public function testManyToManyRelationSaving() {
        $component = new M2MTest();

        $component->RTC1[0]->name = '1';
        $component->RTC1[1]->name = '2';
        $component->name = '2';
        
        $count = $this->dbh->count();

        $component->save();

        $this->assertEqual($this->dbh->count(), ($count + 5));
        
        $this->assertEqual($component->RTC1->count(), 2);
        
        $component = $component->getTable()->find($component->id);
        
        $this->assertEqual($component->RTC1->count(), 2);

        // check that it doesn't matter saving the other M2M components as well

        $component->RTC2[0]->name = '1';
        $component->RTC2[1]->name = '2';

        $count = $this->dbh->count();

        $component->save();

        $this->assertEqual($this->dbh->count(), ($count + 4));

        $this->assertEqual($component->RTC2->count(), 2);

        $component = $component->getTable()->find($component->id);

        $this->assertEqual($component->RTC2->count(), 2);

    }

    public function testManyToManyRelationSaving2() {
        $component = new M2MTest();

        $component->RTC2[0]->name = '1';
        $component->RTC2[1]->name = '2';
        $component->name = '2';
        
        $count = $this->dbh->count();

        $component->save();

        $this->assertEqual($this->dbh->count(), ($count + 5));
        
        $this->assertEqual($component->RTC2->count(), 2);
        
        $component = $component->getTable()->find($component->id);

        $this->assertEqual($component->RTC2->count(), 2);

        // check that it doesn't matter saving the other M2M components as well

        $component->RTC1[0]->name = '1';
        $component->RTC1[1]->name = '2';

        $count = $this->dbh->count();

        $component->save();

        $this->assertEqual($this->dbh->count(), ($count + 4));
        
        $this->assertEqual($component->RTC1->count(), 2);
        
        $component = $component->getTable()->find($component->id);
        
        $this->assertEqual($component->RTC1->count(), 2);

    }
}
?>
