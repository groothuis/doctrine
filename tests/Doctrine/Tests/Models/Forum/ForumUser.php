<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="forum_users")
 */
class ForumUser
{
    /**
     * @DoctrineColumn(type="integer")
     * @DoctrineId
     * @DoctrineGeneratedValue(strategy="auto")
     */
    public $id;
    /**
     * @DoctrineColumn(type="string", length=50)
     */
    public $username;
    /**
     * @DoctrineOneToOne(targetEntity="ForumAvatar", cascade={"save"})
     * @DoctrineJoinColumn(name="avatar_id", referencedColumnName="id")
     */
    public $avatar;
    
    public function getId() {
    	return $this->id;
    }
    
    public function getUsername() {
    	return $this->username;
    }
    
    public function getAvatar() {
    	return $this->avatar;
    }
    
    public function setAvatar(CmsAvatar $avatar) {
    	$this->avatar = $avatar;
    }
}