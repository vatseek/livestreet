<?php

require_once(realpath((dirname(__FILE__)) . "/../AbstractFixtures.php"));

class UserFixtures extends AbstractFixtures
{
    public static function getOrder()
    {
        return 0;
    }

    public function load()
    {

        $oUserFirst = $this->_createUser('user_first', 'qwerty','user_first@info.com', '2012-11-1 00:10:20');
        $this->addReference('user-first', $oUserFirst);

        $oUserFirst->getId();
        $oUserFirst->setProfileName('UserFirst FullName');
        $oUserFirst->setProfileAbout('...  UserFirst profile description');
        $oUserFirst->setProfileSex('man');

        $this->oEngine->User_Update($oUserFirst);
        $this->addReference('user-first', $oUserFirst);

    }

    /**
     * Create user with default values
     *
     * @param string $sUserName
     * @param string $sPassword
     * @param string $sMail
     * @param string $sDate
     *
     * @return ModuleTopic_EntityUser
     */
    private function _createUser($sUserName, $sPassword,$sMail,$sDate)
    {
        $oUser = Engine::GetEntity('User');
         /* @var $oUser ModuleTopic_EntityUser */
        $oUser->setLogin($sUserName);
        $oUser->setPassword(md5($sPassword));
        $oUser->setMail($sMail);
        $oUser->setUserDateRegister($sDate);
        $oUser->setUserIpRegister('127.0.0.1');
        $oUser->setUserActivate('1');
        $oUser->setUserActivateKey('0');

        $this->oEngine->User_Add($oUser);

        return $oUser;
    }

}