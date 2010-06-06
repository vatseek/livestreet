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
 * Модуль управления плагинами сообщений
 *
 */
class ModulePlugin extends Module {
	/**
	 * Файл содержащий информацию об активированных плагинах
	 *
	 * @var string
	 */
	const PLUGIN_ACTIVATION_FILE = 'plugins.dat';
	/**
	 * Файл описания плагина
	 *
	 * @var string 
	 */
	const PLUGIN_XML_FILE = 'plugin.xml';
	/**
	 * Путь к директории с плагинами
	 * 
	 * @var string
	 */
	protected $sPluginsDir;
	
	/**
	 * Список плагинов
	 *
	 * @var unknown_type
	 */
	protected $aPluginsList=array();
	
	/**
	 * Список engine-rewrite`ов (модули, экшены, сущности, шаблоны)
	 *
	 * @var array
	 */
	protected $aDelegates=array(
		'module' => array(),
		'mapper' => array(),
		'action' => array(),
		'entity' => array(),
		'template' => array()
	);
	
	/**
	 * Стек наследований
	 *
	 * @var array
	 */
	protected $aInherits=array();	
	
	/**
	 * Инициализация модуля
	 *
	 */
	public function Init() {
		$this->sPluginsDir=Config::Get('path.root.server').'/plugins/';
	}
	
	/**
	 * Получает список информации о всех плагинах, загруженных в plugin-директорию
	 *
	 * @return array
	 */
	public function GetList() {
		if ($aPaths=glob($this->sPluginsDir.'*',GLOB_ONLYDIR)) {
			$aList=array_map('basename',$aPaths);
			$aActivePlugins=$this->GetActivePlugins();
			foreach($aList as $sPlugin) {
				$this->aPluginsList[$sPlugin] = array(
				'code'      => $sPlugin,
				'is_active' => in_array($sPlugin,$aActivePlugins)
				);

				/**
			 	* Считываем данные из XML файла описания
			 	*/
				$sPluginXML = $this->sPluginsDir.$sPlugin.'/'.self::PLUGIN_XML_FILE;
				if($oXml = @simplexml_load_file($sPluginXML)) {
					/**
				 	* Обрабатываем данные, считанные из XML-описания
				 	*/
					$sLang=$this->Lang_GetLang();

					$this->Xlang($oXml,'name',$sLang);
					$this->Xlang($oXml,'author',$sLang);
					$this->Xlang($oXml,'description',$sLang);
					$oXml->homepage=$this->Text_Parser($oXml->homepage);

					$this->aPluginsList[$sPlugin]['property']=$oXml;
				} else {
					/**
				 	* Если XML-файл описания отсутствует, или не является валидным XML,
				 	* удаляем плагин из списка
				 	*/
					unset($this->aPluginsList[$sPlugin]);
				}
			}
		}
		return $this->aPluginsList;
	}
	
	/**
	 * Получает значение параметра из XML на основе языковой разметки
	 *
	 * @param SimpleXMLElement $oXml
	 * @param string           $sProperty
	 * @param string           $sLang
	 */
	protected function Xlang($oXml,$sProperty,$sLang) {
		$sProperty=trim($sProperty);
		
		$oXml->$sProperty->data = count($data=$oXml->xpath("{$sProperty}/lang[@name='{$sLang}']")) 
			? $this->Text_Parser(trim((string)array_shift($data)))
			: $this->Text_Parser(trim((string)array_shift($oXml->xpath("{$sProperty}/lang[@name='default']"))));	
	}
	
	public function Toggle($sPlugin,$sAction) {
		$aPlugins=$this->GetList();
		if(!isset($aPlugins[$sPlugin])) return null;
		
		$sPluginName=ucfirst($sPlugin);
		
		switch ($sAction) {
			case 'activate':
			case 'deactivate':
				$sAction=ucfirst($sAction);
				
				$sFile="{$this->sPluginsDir}{$sPlugin}/Plugin{$sPluginName}.class.php";
				if(is_file($sFile)) {
					require_once($sFile);
					
					$sClassName="Plugin{$sPluginName}";
					$oPlugin=new $sClassName;
				
					if($sAction=='Activate') {
						/**
						 * Проверяем совместимость с версией LS 						 
						 */
						if(defined('LS_VERSION') 
							and version_compare(LS_VERSION,$aPlugins[$sPlugin]['property']->requires->livestreet,'=<')) {
								$this->Message_AddError(
									$this->Lang_Get(
										'plugins_activation_version_error',
										array(
											'version'=>$aPlugins[$sPlugin]['property']->requires->livestreet)
										),
									$this->Lang_Get('error'),
									true
								);
								return;
						}
						/**
						 * Проверяем наличие require-плагинов
						 */
						if($aPlugins[$sPlugin]['property']->requires->plugins) {
							$aActivePlugins=$this->GetActivePlugins();
							$iConflict=0;
							foreach ($aPlugins[$sPlugin]['property']->requires->plugins->children() as $sReqPlugin) {
								if(!in_array($sReqPlugin,$aActivePlugins)) {
									$iConflict++;
									$this->Message_AddError(
										$this->Lang_Get('plugins_activation_requires_error',
											array(
												'plugin'=>ucfirst($sReqPlugin)
											)
										),
										$this->Lang_Get('error'),
										true
									);
								}
							}
							if($iConflict) { return; }							
						}
						
						/**
						 * Проверяем, не вступает ли данный плагин в конфликт с уже активированными
						 * (по поводу объявленных делегатов) 
						 */
						$aPluginDelegates=$oPlugin->GetDelegates();
						$aPluginInherits=$oPlugin->GetInherits();
						$iConflict=0;
						foreach ($this->aDelegates as $sGroup=>$aReplaceList) {
							$iCount=0;
							if(isset($aPluginDelegates[$sGroup]) 
								and is_array($aPluginDelegates[$sGroup])
									and $iCount=count($aOverlap=array_intersect_key($aReplaceList,$aPluginDelegates[$sGroup]))) {
										$iConflict+=$iCount;
										foreach ($aOverlap as $sResource=>$aConflict) {
											$this->Message_AddError(
												$this->Lang_Get('plugins_activation_overlap', array(
														'resource'=>$sResource,
														'delegate'=>$aConflict['delegate'],
														'plugin'  =>$aConflict['sign']
												)), 
												$this->Lang_Get('error'), true
											);									
										}
							}
							if(isset($aPluginInherits[$sGroup]) 
								and is_array($aPluginInherits[$sGroup])
									and $iCount=count($aOverlap=array_intersect_key($aReplaceList,$aPluginInherits[$sGroup]))) {
										$iConflict+=$iCount;
										foreach ($aOverlap as $sResource=>$aConflict) {
											$this->Message_AddError(
												$this->Lang_Get('plugins_activation_overlap', array(
														'resource'=>$sResource,
														'delegate'=>$aConflict['delegate'],
														'plugin'  =>$aConflict['sign']
												)), 
												$this->Lang_Get('error'), true
											);									
										}
							}							
							if($iCount){ return; }
						}
						/**
						 * Проверяем на конфликт с наследуемыми классами
						 */
						$iConflict=0;
						foreach ($aPluginDelegates as $sGroup=>$aReplaceList) {
							foreach ($aReplaceList as $sResource=>$aConflict) {
								if (isset($this->aInherits[$sResource])) {
									$iConflict+=count($this->aInherits[$sResource]['items']);
									foreach ($this->aInherits[$sResource]['items'] as $aItem) {
										$this->Message_AddError(
											$this->Lang_Get('plugins_activation_overlap_inherit', array(
													'resource'=>$sResource,													
													'plugin'  =>$aItem['sign']
											)), 
											$this->Lang_Get('error'), true
										);
									}
								}
							}
						}
						if($iConflict){ return; }						
					}
					
					$bResult=$oPlugin->$sAction();
				} else {
					/**
					 * Исполняемый файл плагина не найден
					 */
					$this->Message_AddError($this->Lang_Get('plugins_activation_file_not_found'),$this->Lang_Get('error'),true);
					return;
				}
				
				if($bResult) {
					/**
					 * Переопределяем список активированных пользователем плагинов
					 */
					$aActivePlugins=$this->GetActivePlugins();
					if($sAction=='Activate') {
						/**
						 * Вносим данные в файл об активации плагина
						 */
						$aActivePlugins[] = $sPlugin;
					} else {
						/**
						 * Вносим данные в файл о деактивации плагина
						 */
						$aIndex=array_keys($aActivePlugins,$sPlugin);
						if(is_array($aIndex)) {
							unset($aActivePlugins[array_shift($aIndex)]);
						}
					}
					$this->SetActivePlugins($aActivePlugins);
				}
				return $bResult;
			
			default:
				return null;
		}
	}
	
	/**
	 * Возвращает список активированных плагинов в системе
	 *
	 * @return array
	 */
	public function GetActivePlugins() {
		/**
		 * Читаем данные из файла PLUGINS.DAT
		 */		
		$aPlugins=@file($this->sPluginsDir.self::PLUGIN_ACTIVATION_FILE);
		$aPlugins =(is_array($aPlugins))?array_unique(array_map('trim',$aPlugins)):array();
		
		return $aPlugins;
	}
	
	/**
	 * Записывает список активных плагинов в файл PLUGINS.DAT
	 *
	 * @param array|string $aPlugins
	 */
	public function SetActivePlugins($aPlugins) {
		if(!is_array($aPlugins)) $aPlugins = array($aPlugins);
		$aPlugins=array_unique(array_map('trim',$aPlugins));
		
		/**
		 * Записываем данные в файл PLUGINS.DAT
		 */
		file_put_contents($this->sPluginsDir.self::PLUGIN_ACTIVATION_FILE, implode(PHP_EOL,$aPlugins));
	}
	
	/**
	 * Удаляет плагины с сервера
	 *
	 * @param array $aPlugins
	 */
	public function Delete($aPlugins) {
		if(!is_array($aPlugins)) $aPlugins=array($aPlugins);
		
		$aActivePlugins=$this->GetActivePlugins();
		foreach ($aPlugins as $sPluginCode) {
			/**
			 * Если плагин активен, деактивируем его
			 */
			if(in_array($sPluginCode,$aActivePlugins)) $this->Toggle($sPluginCode,'deactivate');
			
			/**
			 * Удаляем директорию с плагином
			 */
			func_rmdir($this->sPluginsDir.$sPluginCode);
		}
	}
	
	/**
	 * Перенаправление вызовов на модули, экшены, сущности
	 *
	 * @param  string $sType
	 * @param  string $sFrom
	 * @param  string $sTo
	 * @param  string $sSign
	 */
	public function Delegate($sType,$sFrom,$sTo,$sSign=__CLASS__) {
		/**
		 * Запрещаем неподписанные делегаты
		 */
		if(!is_string($sSign) or !strlen($sSign)) return null;
		if(!in_array($sType,array_keys($this->aDelegates)) or !$sFrom or !$sTo) return null;
		
		$this->aDelegates[$sType][trim($sFrom)]=array(
			'delegate'=>trim($sTo),
			'sign'=>$sSign
		);
	}

	/**
	 * Добавляет в стек наследника класса
	 *
	 * @param string $sFrom
	 * @param string $sTo
	 * @param string $sSign	
	 */
	public function Inherit($sFrom,$sTo,$sSign=__CLASS__) {
		if(!is_string($sSign) or !strlen($sSign)) return null;
		if(!$sFrom or !$sTo) return null;
				
		$this->aInherits[trim($sFrom)]['items'][]=array(
			'inherit'=>trim($sTo),
			'sign'=>$sSign
		);
		$this->aInherits[trim($sFrom)]['position']=count($this->aInherits[trim($sFrom)]['items'])-1;
	}
	
	/**
	 * Получает следующего родителя у наследника.
	 * ВНИМАНИЕ! Данный метод нужно вызвать только из __autoload()
	 *
	 * @param unknown_type $sFrom
	 * @return unknown
	 */
	public function GetParentInherit($sFrom) {		
		if (!isset($this->aInherits[$sFrom]['items']) or count($this->aInherits[$sFrom]['items'])<=1 or $this->aInherits[$sFrom]['position']<1) {
			return $sFrom;
		}
		$this->aInherits[$sFrom]['position']--;		
		return $this->aInherits[$sFrom]['items'][$this->aInherits[$sFrom]['position']]['inherit'];
	}
	
	public function GetLastInherit($sFrom) {
		if (isset($this->aInherits[trim($sFrom)])) {
			return $this->aInherits[trim($sFrom)]['items'][count($this->aInherits[trim($sFrom)]['items'])-1];
		}
		return null;
	}
	/**
	 * Возвращает делегат модуля, экшена, сущности. 
	 * Если делегат не определен, пытается найти наследника, иначе отдает переданный в качестве sender`a параметр
	 *
	 * @param  string $sType
	 * @param  string $sFrom
	 * @return string
	 */
	public function GetDelegate($sType,$sFrom) {		
		if (isset($this->aDelegates[$sType][$sFrom]['delegate'])) {			
			return $this->aDelegates[$sType][$sFrom]['delegate'];
		} elseif ($aInherit=$this->GetLastInherit($sFrom)) {			
			return $aInherit['inherit'];
		}
		return $sFrom;
	}

	/**
	 * Возвращает делегирующий объект по имени делегата
	 * 
	 * @param  string $sType Объект
	 * @param  string $sTo   Делегат
	 * @return string
	 */
	public function GetDelegater($sType,$sTo) {
		$aDelegateMapper=array_filter(
			$this->aDelegates[$sType], 
			create_function('$item','return $item["delegate"]=="'.$sTo.'";')
		);
		if (is_array($aDelegateMapper) and count($aDelegateMapper))	{
			return array_shift(array_keys($aDelegateMapper));
		}		
		foreach ($this->aInherits as $k=>$v) {
			$aInheritMapper=array_filter(
				$v['items'],
				create_function('$item','return $item["inherit"]=="'.$sTo.'";')
			);
			if (is_array($aInheritMapper) and count($aInheritMapper))	{
				return $k;
			}
		}
		return $sTo;		
	}
	
	/**
	 * Возвращает подпись делегата модуля, экшена, сущности. 
	 *
	 * @param  string $sType
	 * @param  string $sFrom
	 * @return string|null
	 */
	public function GetDelegateSign($sType,$sFrom) {
		if (isset($this->aDelegates[$sType][$sFrom]['sign'])) {
			return $this->aDelegates[$sType][$sFrom]['sign'];
		}
		if ($aInherit=$this->GetLastInherit($sFrom)) {
			return $aInherit['sign'];
		}
		return null;
	}
	
	/**
	 * Возвращает true, если установлено правило делегирования 
	 * и класс является базовым в данном правиле
	 *
	 * @param  string $sType
	 * @param  string $sFrom
	 * @return bool
	 */
	public function isDelegater($sType,$sFrom) {
		if (isset($this->aDelegates[$sType][$sFrom]['delegate'])) {			
			return true;
		} elseif ($aInherit=$this->GetLastInherit($sFrom)) {			
			return true;
		}
		return false;
	}
	
	/**
	 * Возвращает true, если устано
	 * 
	 * @param  string $sType
	 * @param  string $sTo
	 * @return bool
	 */
	public function isDelegated($sType,$sTo) {
		/**
		 * Фильтруем меппер делегатов/наследников
		 * @var array
		 */
		$aDelegateMapper=array_filter(
			$this->aDelegates[$sType], 
			create_function('$item','return $item["delegate"]=="'.$sTo.'";')
		);
		if (is_array($aDelegateMapper) and count($aDelegateMapper))	{
			return true;
		}		
		foreach ($this->aInherits as $k=>$v) {
			$aInheritMapper=array_filter(
				$v['items'],
				create_function('$item','return $item["inherit"]=="'.$sTo.'";')
			);
			if (is_array($aInheritMapper) and count($aInheritMapper))	{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Возвращает список объектов, доступных для делегирования
	 * 
	 * @return array
	 */
	public function GetDelegateObjectList() {
		return array_keys($this->aDelegates);
	}
	
	/**
	 * При завершении работы модуля
	 *
	 */
	public function Shutdown() {
	}
}
?>