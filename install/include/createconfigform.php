<?php
/**
 * WhoIS REST Services API
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Chronolabs Cooperative http://syd.au.snails.email
 * @license         ACADEMIC APL 2 (https://sourceforge.net/u/chronolabscoop/wiki/Academic%20Public%20License%2C%20version%202.0/)
 * @license         GNU GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @package         whois-api
 * @since           2.2.13
 * @author          Dr. Simon Antony Roberts <simon@snails.email>
 * @version         2.2.14
 * @description		A REST API Interface which retrieves IPv4, IPv6, TLD, gLTD Whois Data
 * @link            http://internetfounder.wordpress.com
 * @link            https://github.com/Chronolabs-Cooperative/WhoIS-API-PHP
 * @link            https://sourceforge.net/p/chronolabs-cooperative
 * @link            https://facebook.com/ChronolabsCoop
 * @link            https://twitter.com/ChronolabsCoop
 * 
 */


if (!defined('API_INSTALL')) {
    die('API Custom Installation die');
}

include_once API_ROOT_PATH . '/class/apiformloader.php';
include_once API_ROOT_PATH . '/class/apilists.php';

define('PREF_1', _MD_AM_GENERAL);
define('PREF_2', _MD_AM_USERSETTINGS);
define('PREF_3', _MD_AM_METAFOOTER);
define('PREF_4', _MD_AM_CENSOR);
define('PREF_5', _MD_AM_SEARCH);
define('PREF_6', _MD_AM_MAILER);
if (defined('_MD_AM_AUTHENTICATION')) {
    define('PREF_7', _MD_AM_AUTHENTICATION);
}

/**
 * @param $config
 *
 * @return array
 */
function createConfigform($config)
{
    api_load('APIFormRendererBootstrap3');
    APIFormRenderer::getInstance()->set(new APIFormRendererBootstrap3());

    /* @var $config_handler APIConfigHandler  */
    $config_handler         = api_getHandler('config');
    $GLOBALS['apiConfig'] = $apiConfig = $config_handler->getConfigsByCat(API_CONF);

    $ret       = array();
    $confcount = count($config);

    for ($i = 0; $i < $confcount; ++$i) {
        $conf_catid = $config[$i]->getVar('conf_catid');
        if (!isset($ret[$conf_catid])) {
            $form_title       = constant('PREF_' . $conf_catid);
            $ret[$conf_catid] = new APIThemeForm($form_title, 'configs', 'index.php', 'post');
        }

        $title = constant($config[$i]->getVar('conf_title'));

        switch ($config[$i]->getVar('conf_formtype')) {
            case 'textarea':
                $myts = MyTextSanitizer::getInstance();
                if ($config[$i]->getVar('conf_valuetype') === 'array') {
                    // this is exceptional.. only when value type is arrayneed a smarter way for this
                    $ele = ($config[$i]->getVar('conf_value') != '') ? new APIFormTextArea($title, $config[$i]->getVar('conf_name'), $myts->htmlspecialchars(implode('|', $config[$i]->getConfValueForOutput())), 5, 50) : new APIFormTextArea($title, $config[$i]->getVar('conf_name'), '', 5, 50);
                } else {
                    $ele = new APIFormTextArea($title, $config[$i]->getVar('conf_name'), $myts->htmlspecialchars($config[$i]->getConfValueForOutput()), 5, 100);
                }
                break;

            case 'select':
                $ele     = new APIFormSelect($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
                $options = $config_handler->getConfigOptions(new Criteria('conf_id', $config[$i]->getVar('conf_id')));
                $opcount = count($options);
                for ($j = 0; $j < $opcount; ++$j) {
                    $optval = defined($options[$j]->getVar('confop_value')) ? constant($options[$j]->getVar('confop_value')) : $options[$j]->getVar('confop_value');
                    $optkey = defined($options[$j]->getVar('confop_name')) ? constant($options[$j]->getVar('confop_name')) : $options[$j]->getVar('confop_name');
                    $ele->addOption($optval, $optkey);
                }
                break;

            case 'select_multi':
                $ele     = new APIFormSelect($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), 5, true);
                $options = $config_handler->getConfigOptions(new Criteria('conf_id', $config[$i]->getVar('conf_id')));
                $opcount = count($options);
                for ($j = 0; $j < $opcount; ++$j) {
                    $optval = defined($options[$j]->getVar('confop_value')) ? constant($options[$j]->getVar('confop_value')) : $options[$j]->getVar('confop_value');
                    $optkey = defined($options[$j]->getVar('confop_name')) ? constant($options[$j]->getVar('confop_name')) : $options[$j]->getVar('confop_name');
                    $ele->addOption($optval, $optkey);
                }
                break;

            case 'yesno':
                $ele = new APIFormRadioYN($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), _YES, _NO);
                break;

            case 'theme':
            case 'theme_multi':
                $ele = ($config[$i]->getVar('conf_formtype') !== 'theme_multi') ? new APIFormSelect($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput()) : new APIFormSelect($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), 5, true);
                require_once API_ROOT_PATH . '/class/apilists.php';
                $dirlist = APILists::getThemesList();
                if (!empty($dirlist)) {
                    asort($dirlist);
                    $ele->addOptionArray($dirlist);
                }
                // old theme value is used to determine whether to update cache or not. kind of dirty way
                $form->addElement(new APIFormHidden('_old_theme', $config[$i]->getConfValueForOutput()));
                break;

            case 'tplset':
                $ele            = new APIFormSelect($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
                $tplset_handler = api_getHandler('tplset');
                $tplsetlist     = $tplset_handler->getList();
                asort($tplsetlist);
                foreach ($tplsetlist as $key => $name) {
                    $ele->addOption($key, $name);
                }
                // old theme value is used to determine whether to update cache or not. kind of dirty way
                $form->addElement(new APIFormHidden('_old_theme', $config[$i]->getConfValueForOutput()));
                break;

            case 'timezone':
                $ele = new APIFormSelectTimezone($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
                break;

            case 'language':
                $ele = new APIFormSelectLang($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
                break;

            case 'startpage':
                $ele            = new APIFormSelect($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
                /* @var $module_handler APIModuleHandler */
                $module_handler = api_getHandler('module');
                $criteria       = new CriteriaCompo(new Criteria('hasmain', 1));
                $criteria->add(new Criteria('isactive', 1));
                $moduleslist       = $module_handler->getList($criteria, true);
                $moduleslist['--'] = _MD_AM_NONE;
                $ele->addOptionArray($moduleslist);
                break;

            case 'group':
                $ele = new APIFormSelectGroup($title, $config[$i]->getVar('conf_name'), false, $config[$i]->getConfValueForOutput(), 1, false);
                break;

            case 'group_multi':
                $ele = new APIFormSelectGroup($title, $config[$i]->getVar('conf_name'), false, $config[$i]->getConfValueForOutput(), 5, true);
                break;

            // RMV-NOTIFY - added 'user' and 'user_multi'
            case 'user':
                $ele = new APIFormSelectUser($title, $config[$i]->getVar('conf_name'), false, $config[$i]->getConfValueForOutput(), 1, false);
                break;

            case 'user_multi':
                $ele = new APIFormSelectUser($title, $config[$i]->getVar('conf_name'), false, $config[$i]->getConfValueForOutput(), 5, true);
                break;

            case 'module_cache':
                /* @var $module_handler APIModuleHandler */
                $module_handler = api_getHandler('module');
                $modules        = $module_handler->getObjects(new Criteria('hasmain', 1), true);
                $currrent_val   = $config[$i]->getConfValueForOutput();
                $cache_options  = array(
                    '0'      => _NOCACHE,
                    '30'     => sprintf(_SECONDS, 30),
                    '60'     => _MINUTE,
                    '300'    => sprintf(_MINUTES, 5),
                    '1800'   => sprintf(_MINUTES, 30),
                    '3600'   => _HOUR,
                    '18000'  => sprintf(_HOURS, 5),
                    '86400'  => _DAY,
                    '259200' => sprintf(_DAYS, 3),
                    '604800' => _WEEK);
                if (count($modules) > 0) {
                    $ele = new APIFormElementTray($title, '<br>');
                    foreach (array_keys($modules) as $mid) {
                        $c_val   = isset($currrent_val[$mid]) ? (int)$currrent_val[$mid] : null;
                        $selform = new APIFormSelect($modules[$mid]->getVar('name'), $config[$i]->getVar('conf_name') . "[$mid]", $c_val);
                        $selform->addOptionArray($cache_options);
                        $ele->addElement($selform);
                        unset($selform);
                    }
                } else {
                    $ele = new APIFormLabel($title, _MD_AM_NOMODULE);
                }
                break;

            case 'site_cache':
                $ele = new APIFormSelect($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
                $ele->addOptionArray(array(
                                         '0'      => _NOCACHE,
                                         '30'     => sprintf(_SECONDS, 30),
                                         '60'     => _MINUTE,
                                         '300'    => sprintf(_MINUTES, 5),
                                         '1800'   => sprintf(_MINUTES, 30),
                                         '3600'   => _HOUR,
                                         '18000'  => sprintf(_HOURS, 5),
                                         '86400'  => _DAY,
                                         '259200' => sprintf(_DAYS, 3),
                                         '604800' => _WEEK));
                break;

            case 'password':
                $myts = MyTextSanitizer::getInstance();
                $ele  = new APIFormPassword($title, $config[$i]->getVar('conf_name'), 50, 255, $myts->htmlspecialchars($config[$i]->getConfValueForOutput()));
                break;

            case 'color':
                $myts = MyTextSanitizer::getInstance();
                $ele  = new APIFormColorPicker($title, $config[$i]->getVar('conf_name'), $myts->htmlspecialchars($config[$i]->getConfValueForOutput()));
                break;

            case 'hidden':
                $myts = MyTextSanitizer::getInstance();
                $ele  = new APIFormHidden($config[$i]->getVar('conf_name'), $myts->htmlspecialchars($config[$i]->getConfValueForOutput()));
                break;

            case 'textbox':
            default:
                $myts = MyTextSanitizer::getInstance();
                $ele  = new APIFormText($title, $config[$i]->getVar('conf_name'), 50, 255, $myts->htmlspecialchars($config[$i]->getConfValueForOutput()));
                break;
        }

        if (defined($config[$i]->getVar('conf_desc')) && constant($config[$i]->getVar('conf_desc')) != '') {
            $ele->setDescription(constant($config[$i]->getVar('conf_desc')));
        }
        $ret[$conf_catid]->addElement($ele);

        $hidden = new APIFormHidden('conf_ids[]', $config[$i]->getVar('conf_id'));
        $ret[$conf_catid]->addElement($hidden);

        unset($ele, $hidden);
    }

    return $ret;
}

/**
 * @param $config
 *
 * @return array
 */
function createThemeform($config)
{
    api_load('APIFormRendererBootstrap3');
    APIFormRenderer::getInstance()->set(new APIFormRendererBootstrap3());

    $title          = (!defined($config->getVar('conf_desc')) || constant($config->getVar('conf_desc')) === '') ? constant($config->getVar('conf_title')) : constant($config->getVar('conf_title')) . '<br><br><span>' . constant($config->getVar('conf_desc')) . '</span>';
    $form_theme_set = new APIFormSelect('', $config->getVar('conf_name'), $config->getConfValueForOutput(), 1, false);
    $dirlist        = APILists::getThemesList();
    if (!empty($dirlist)) {
        asort($dirlist);
        $form_theme_set->addOptionArray($dirlist);
    }

    $label_content = '';

    // read ini file for each theme
    foreach ($dirlist as $theme) {
        // set default value
        $theme_ini = array(
            'Name'        => $theme,
            'Description' => '',
            'Version'     => '',
            'Format'      => '',
            'Author'      => '',
            'Demo'        => '',
            'Url'         => '',
            'Download'    => '',
            'W3C'         => '',
            'Licence'     => '',
            'thumbnail'   => 'screenshot.gif',
            'screenshot'  => 'screenshot.png');

        if ($theme == $config->getConfValueForOutput()) {
            $label_content .= '<div class="theme_preview" id="'.$theme.'" style="display:block;">';
        } else {
            $label_content .= '<div class="theme_preview" id="'.$theme.'" style="display:none;">';
        }
        if (file_exists(API_ROOT_PATH . "/themes/$theme/theme.ini")) {
            $theme_ini = parse_ini_file(API_ROOT_PATH . "/themes/$theme/theme.ini");
            if ($theme_ini['screenshot'] == '') {
                $theme_ini['screenshot'] = 'screenshot.png';
                $theme_ini['thumbnail']  = 'thumbnail.png';
            }
        }

        if ($theme_ini['screenshot'] !== '' && file_exists(API_ROOT_PATH . '/themes/' . $theme . '/' . $theme_ini['screenshot'])) {
            $label_content .= '<img class="img-responsive" src="' . API_URL . '/themes/' . $theme . '/' . $theme_ini['screenshot'] . '" alt="Screenshot" />';
        } elseif ($theme_ini['thumbnail'] !== '' && file_exists(API_ROOT_PATH . '/themes/' . $theme .'/' . $theme_ini['thumbnail'])) {
            $label_content .= '<img class="img-responsive" src="' . API_URL . '/themes/' . $theme . '/' . $theme_ini['thumbnail'] . '" alt="$theme" />';
        } else {
            $label_content .= THEME_NO_SCREENSHOT;
        }
        $label_content .= '</div>';
    }
    // read ini file for each theme

    $form_theme_set->setExtra("onchange='showThemeSelected(this)'");

    $form = new APIThemeForm($title, 'themes', 'index.php', 'post');
    $form->addElement($form_theme_set);
    $form->addElement(new APIFormLabel('', "<div id='screenshot'>" . $label_content . '</div>'));

    $form->addElement(new APIFormHidden('conf_ids[]', $config->getVar('conf_id')));

    return $ret = array($form);
}
