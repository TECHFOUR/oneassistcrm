{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
<tr>

                    <td colspan="4">
 {*if $MODULE eq 'Events' && vtranslate(vtranslate($BLOCK_LABEL, $MODULE)) eq 'Related Lead'*}
                        {if $MODULE eq 'Events'}

                            {include file="LineItemsEdit.tpl"|@vtemplate_path:$MODULE}

                        {/if}

                        {* end code added by ajay *}

                    </td>
 </tr>
{/strip}

