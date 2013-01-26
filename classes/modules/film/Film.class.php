<?php
/*-------------------------------------------------------
*
*   LiveStreet Engine Social Networking
*   Copyright © 2008 Mzhelskiy Maxim
*
*--------------------------------------------------------
*
*   Official site: www.livestreet.ru
*   Contact e-mail: rus.engine@gmail.com
*
*   GNU General Public License, version 2:
*   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
---------------------------------------------------------
*/

/**
 * Модуль для работы с топиками
 *
 * @package modules.topic
 * @since 1.0
 */
class ModuleFilm extends Module {

	protected $oFilmMapper;
	protected $oUserCurrent=null;

	public function Init() {
		$this->oFilmMapper=Engine::GetMapper(__CLASS__);
		$this->oUserCurrent=$this->User_GetUserCurrent();
	}

    public function GetFilmByUrl($sFilmUrl)
    {
        $sFilmKeyName = func_build_cache_keys(array($sFilmUrl),'film_');
        if (!$this->Cache_Get($sFilmKeyName)) {
            var_dump('111');
        }

        var_dump($sFilmUrl);
    }
}
?>