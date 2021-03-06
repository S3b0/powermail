<?php
namespace In2code\Powermail\Utility\Tca;

use In2code\Powermail\Utility\BackendUtility;
use In2code\Powermail\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Alex Kellner <alexander.kellner@in2code.de>, in2code.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class ShowFormNoteEditForm to display chosen form and some
 * more information in the FlexForm of an opened powermail
 * content element
 *
 * @package In2code\Powermail\Utility\Tca
 */
class ShowFormNoteEditForm
{

    /**
     * Params
     *
     * @var array
     */
    public $params;

    /**
     * Path to locallang file (with : as postfix)
     *
     * @var string
     */
    protected $locallangPath = 'LLL:EXT:powermail/Resources/Private/Language/locallang_db.xlf:';

    /**
     * @var \TYPO3\CMS\Lang\LanguageService
     */
    protected $languageService = null;

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * Show Note which form was selected
     *
     * @param array $params Config Array
     * @return string
     */
    public function showNote($params)
    {
        $this->initialize($params);
        return $this->getInformationMarkup();
    }

    /**
     * @return string
     */
    protected function getInformationMarkup()
    {
        $formUid = $this->getRelatedForm();
        if ($formUid === 0) {
            return $this->getInformationErrorMarkup();
        }
        $content = '
			<table cellspacing="0" cellpadding="0" border="0" class="typo3-dblist" style="border: 1px solid #d7d7d7;">
				<tbody>
					<tr class="t3-row-header">
						<td nowrap="nowrap" style="padding: 5px; color: white">
							<span class="c-table">
								' . $this->getLabel('formnote.formname') . '
							</span>
						</td>
						<td nowrap="nowrap" style="padding: 5px; color: white">
							<span class="c-table">
								' . $this->getLabel('formnote.storedinpage') . '
							</span>
						</td>
						<td nowrap="nowrap" style="padding: 5px; color: white">
							<span class="c-table">
								' . $this->getLabel('formnote.pages') . '
							</span>
						</td>
						<td nowrap="nowrap" style="padding: 5px; color: white">
							<span class="c-table">
								' . $this->getLabel('formnote.fields') . '
							</span>
						</td>
						<td nowrap="nowrap" style="padding: 5px; color: white">
							<span class="c-table">
								&nbsp;
							</span>
						</td>
					</tr>
					<tr class="db_list_normal">
						<td nowrap="nowrap" class="col-title" style="padding: 5px;">
							<a title="Edit" href="' . $this->getEditFormLink($formUid) . '">
								' . htmlspecialchars($this->getFormPropertyFromUid($formUid, 'title')) . '
							</a>
						</td>
						<td nowrap="nowrap" class="col-title" style="padding: 5px;">
							<a title="id=' . $this->getFormPropertyFromUid($formUid, 'pid') . '"
								onclick="top.loadEditId(' . (int) $this->getFormPropertyFromUid($formUid, 'pid') . '
								,&quot;&amp;SET[language]=0&quot;); return false;" href="#">
								' . htmlspecialchars($this->getPageNameFromUid($this->getFormPropertyFromUid($formUid, 'pid'))) . '
							</a>
						</td>
						<td nowrap="nowrap" class="col-title" style="padding: 5px;">
							<span title="' . htmlspecialchars(implode(', ', $this->getPagesFromForm($formUid))) . '">
								' . count($this->getPagesFromForm($formUid)) . '
							</span>
						</td>
						<td nowrap="nowrap" class="col-title" style="padding: 5px;">
							<span title="' . htmlspecialchars(implode(', ', $this->getFieldsFromForm($formUid))) . '">
								' . count($this->getFieldsFromForm($formUid)) . '
							</span>
						</td>
						<td nowrap="nowrap" class="col-icon" style="padding: 5px;">
							<a title="Edit" href="' . $this->getEditFormLink($formUid) . '">
								<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-open"
									title="Edit Form">
									&nbsp;
								</span>
							</a>
						</td>
					</tr>
				</tbody>
			</table>
		';
        return $content;
    }

    /**
     * Get error markup
     *
     * @return string
     */
    protected function getInformationErrorMarkup()
    {
        $content = '<div style="padding-bottom: 5px;">';
        $content .= $this->getLabel('formnote.noform');
        $content .= '</div>';
        return $content;
    }

    /**
     * Build URI for edit link
     *
     * @param int $formUid
     * @return string
     */
    protected function getEditFormLink($formUid)
    {
        return BackendUtility::createEditUri('tx_powermail_domain_model_forms', $formUid);
    }

    /**
     * Get localized label
     *
     * @param string $key
     * @return string
     */
    protected function getLabel($key)
    {
        return $this->languageService->sL($this->locallangPath . 'flexform.main.' . $key, true);
    }

    /**
     * Get related form
     *
     * @return int
     */
    protected function getRelatedForm()
    {
        $formUid = 0;

        if (is_array($this->params['row']['pi_flexform'])) {
            // TYPO3 7.5 and newer delivers an array
            $formUid = (int) $this->params['row']['pi_flexform']['data']['main']['lDEF']
            ['settings.flexform.main.form']['vDEF'][0];
        } else {
            // TYPO3 7.4 or older delivers a string
            $flexForm = GeneralUtility::xml2array($this->params['row']['pi_flexform']);
            if (
                is_array($flexForm) &&
                isset($flexForm['data']['main']['lDEF']['settings.flexform.main.form']['vDEF'])
            ) {
                $formUid = (int) $flexForm['data']['main']['lDEF']['settings.flexform.main.form']['vDEF'];
            }
        }

        $formUid = $this->getLocalizedFormUid($formUid, $this->params['row']['sys_language_uid']);
        return $formUid;
    }

    /**
     * Get form uid of a localized form
     *
     * @param int $uid
     * @param int $sysLanguageUid
     * @return int
     */
    protected function getLocalizedFormUid($uid, $sysLanguageUid)
    {
        if ($sysLanguageUid > 0) {
            $select = 'uid';
            $from = 'tx_powermail_domain_model_forms';
            $where = 'sys_language_uid=' . (int) $sysLanguageUid . ' and l10n_parent=' . (int) $uid;
            $row = $this->databaseConnection->exec_SELECTgetSingleRow($select, $from, $where);
            if (!empty($row['uid'])) {
                $uid = (int) $row['uid'];
            }
        }
        return $uid;
    }

    /**
     * @param int $uid
     * @param string $property
     * @return string
     */
    protected function getFormPropertyFromUid($uid, $property)
    {
        $select = '*';
        $from = 'tx_powermail_domain_model_forms';
        $where = 'uid = ' . (int) $uid;
        $groupBy = '';
        $orderBy = '';
        $limit = 1;
        $res = $this->databaseConnection->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        $row = $this->databaseConnection->sql_fetch_assoc($res);
        if (isset($row[$property])) {
            return $row[$property];
        }
        return '';
    }

    /**
     * @param int $uid
     * @return string
     */
    protected function getPageNameFromUid($uid)
    {
        $select = 'title';
        $from = 'pages';
        $where = 'uid = ' . (int) $uid;
        $groupBy = '';
        $orderBy = '';
        $limit = 1;
        $res = $this->databaseConnection->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        $row = $this->databaseConnection->sql_fetch_assoc($res);
        if (isset($row['title'])) {
            return $row['title'];
        }
        return '';
    }

    /**
     * Get array with related pages to a form
     *
     * @param int $uid
     * @return array
     */
    protected function getPagesFromForm($uid)
    {
        if (ConfigurationUtility::isReplaceIrreWithElementBrowserActive()) {
            return $this->getPagesFromFormAlternative($uid);
        }
        $result = [];
        $select = 'p.title';
        $from = 'tx_powermail_domain_model_forms fo LEFT JOIN tx_powermail_domain_model_pages p ON p.forms = fo.uid';
        $where = 'fo.uid = ' . (int) $uid . ' and p.deleted = 0';
        $groupBy = '';
        $orderBy = '';
        $limit = 1000;
        $res = $this->databaseConnection->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res) {
            while (($row = $this->databaseConnection->sql_fetch_assoc($res))) {
                $result[] = $row['title'];
            }
        }
        return $result;
    }

    /**
     * Get array with related fields to a form
     *
     * @param int $uid
     * @return array
     */
    protected function getFieldsFromForm($uid)
    {
        if (ConfigurationUtility::isReplaceIrreWithElementBrowserActive()) {
            return $this->getFieldsFromFormAlternative($uid);
        }
        $result = [];
        $select = 'f.title';
        $from = 'tx_powermail_domain_model_forms fo ' .
            'LEFT JOIN tx_powermail_domain_model_pages p ON p.forms = fo.uid ' .
            'LEFT JOIN tx_powermail_domain_model_fields f ON f.pages = p.uid';
        $where = 'fo.uid = ' . (int) $uid . ' and p.deleted = 0 and f.deleted = 0';
        $groupBy = '';
        $orderBy = '';
        $limit = 1000;
        $res = $this->databaseConnection->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res) {
            while (($row = $this->databaseConnection->sql_fetch_assoc($res))) {
                $result[] = $row['title'];
            }
        }
        return $result;
    }

    /**
     * Get array with related pages to a form
     * if replaceIrreWithElementBrowser is active
     *
     * @param int $uid
     * @return array
     */
    protected function getPagesFromFormAlternative($uid)
    {
        $select = 'f.pages';
        $from = 'tx_powermail_domain_model_forms as f';
        $where = 'f.uid = ' . (int) $uid;
        $pageUids = $this->databaseConnection->exec_SELECTgetRows($select, $from, $where);
        $select = 'p.title';
        $from = 'tx_powermail_domain_model_pages as p';
        $where = 'p.uid in (' . $this->integerList($pageUids[0]['pages']) . ') and p.deleted = 0';
        $pageTitles = $this->databaseConnection->exec_SELECTgetRows($select, $from, $where);
        $pageTitlesReduced = [];
        foreach ($pageTitles as $titleRow) {
            $pageTitlesReduced[] = $titleRow['title'];
        }
        return $pageTitlesReduced;
    }

    /**
     * Get array with related fields to a form
     * if replaceIrreWithElementBrowser is active
     *
     * @param int $uid
     * @return array
     */
    protected function getFieldsFromFormAlternative($uid)
    {
        $select = 'f.pages';
        $from = 'tx_powermail_domain_model_forms as f';
        $where = 'f.uid = ' . (int) $uid;
        $pageUids = $this->databaseConnection->exec_SELECTgetRows($select, $from, $where);
        $select = 'p.uid';
        $from = 'tx_powermail_domain_model_pages as p';
        $where = 'p.uid in (' . $this->integerList($pageUids[0]['pages']) . ') and p.deleted = 0';
        $pageUids = $this->databaseConnection->exec_SELECTgetRows($select, $from, $where);
        $fieldTitlesReduced = [];
        foreach ($pageUids as $uidRow) {
            $select = 'field.title';
            $from = 'tx_powermail_domain_model_fields as field';
            $where = 'field.pages = ' . (int) $uidRow['uid'];
            $fieldTitles = $this->databaseConnection->exec_SELECTgetRows($select, $from, $where);
            foreach ($fieldTitles as $titleRow) {
                $fieldTitlesReduced[] = $titleRow['title'];
            }
        }
        return $fieldTitlesReduced;
    }

    /**
     * Forces an integer list
     *
     * @param string $list
     * @return string
     */
    protected function integerList($list)
    {
        return implode(',', GeneralUtility::intExplode(',', $list));
    }

    /**
     * Initialize some variables
     *
     * @param array $params
     * @return void
     */
    protected function initialize($params)
    {
        $this->params = $params;
        $this->languageService = $GLOBALS['LANG'];
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }
}
