<?php

App::uses('AppHelper', 'View/Helper');

class FieldsetHelper extends AppHelper {
    public function printApiView($field) {
        echo '<tr data-num-collapsed="0" data-name="'.$field->name.'" data-fieldset-path="';
        $path = explode('.', $field->name);
        array_pop($path);
        echo implode('.', $path).'"><td>';
            if (!empty($field->descendantFields)) {
                echo '<a class="fieldset-collapse" onclick="toggleFieldsetCollapse(this)" data-collapsed="false"></a>';
            }
        echo '</td>';
        echo '<td>';
            if (!empty($field->businessTerm[0])) {
                echo '<input type="checkbox"'.
                     ' data-title="'.h($field->businessTerm[0]->term).'"'.
                     ' data-vocabID="'.h($field->businessTerm[0]->termCommunityId).'"'.
                     ' value="'.h($field->businessTerm[0]->termId).'"'.
                     ' class="chk'; if ($field->assetType == 'Fieldset') echo ' fieldset'; echo '"'.
                     ' id="chk'.h($field->businessTerm[0]->termId).'"'.
                     ' data-name="'.$field->name.'"'.
                     ' data-field-id="'.$field->id.'"'.
                     ' data-fieldset-path="';
                     $path = explode('.', $field->name);
                     array_pop($path);
                     echo implode('.', $path).'">';
            } else {
                echo '<input type="checkbox"'.
                     ' data-title="'.$field->name.'"'.
                     ' data-vocabID=""'.
                     ' value=""'.
                     ' class="chk'; if ($field->assetType == 'Fieldset') echo ' fieldset'; echo '"'.
                     ' data-name="'.$field->name.'"'.
                     ' data-field-id="'.$field->id.'"'.
                     ' data-fieldset-path="';
                     $path = explode('.', $field->name);
                     array_pop($path);
                     echo implode('.', $path).'">';
            }
        echo '</td>';
        echo '<td>';
            $fieldPath = explode('.', $field->name);
            for ($i = 0; $i < count($fieldPath) - 1; $i++) {
                echo str_repeat('&nbsp;', 12);
            }
            echo end($fieldPath);
        echo '</td>';
        echo '<td>';
            if (!empty($field->businessTerm[0])) {
                $fieldDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $field->businessTerm[0]->termDescription)))));
                echo '<a href="/search/term/'.$field->businessTerm[0]->termId.'">'.$field->businessTerm[0]->term.'</a>';
                echo '<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$fieldDef.'" class="info"><img src="/img/iconInfo.png"></div>';
            }
        echo '</td>';
        echo '<td style="white-space:nowrap;">';
            if (!empty($field->businessTerm[0])) {
                $classification = $field->businessTerm[0]->termClassification;
                switch($classification) {
                    case 'Public':
                    case '1 - Public':
                        $classificationTitle = 'Public';
                        $classification = 'Public';
                        break;
                    case 'Internal':
                    case '2 - Internal':
                        $classificationTitle = 'Internal';
                        $classification = 'Internal';
                        break;
                    case 'Confidential':
                    case '3 - Confidential':
                        $classificationTitle = 'Confidential';
                        $classification = 'Classified';
                        break;
                    case 'Highly Confidential':
                    case '4 - Highly Confidential':
                        $classificationTitle = 'Highly Confidential';
                        $classification = 'HighClassified';
                        break;
                    case 'Not Applicable':
                    case '0 - N/A':
                        $classificationTitle = 'Not Applicable';
                        $classification = 'NotApplicable';
                        break;
                    default:
                        $classificationTitle = 'Unspecified';
                        $classification = 'NoClassification2';
                        break;
                }
                echo '<img class="classIcon" src="/img/icon'.$classification.'.png">&nbsp;'.$classificationTitle;

                if ($field->businessTerm[0]->approvalStatus != 'Approved') {
                    echo '&nbsp;&nbsp;<img class="pendingApprovalIcon" src="/img/alert.png" onmouseover="displayPendingApproval(this)" onmouseout="hidePendingAproval()">';
                }
            }
        echo '</td><td>';
            if (!empty($field->businessTerm[0])) {
                echo '<a href="/search/listTerms/'.$field->businessTerm[0]->termVocabularyId.'">'.$field->businessTerm[0]->termCommunityName.'</a>';
            }
        echo '</td></tr>';

        if (!empty($field->descendantFields)) {
            foreach ($field->descendantFields as $field) {
                $this->printApiView($field);
            }
        }
    }

    public function printApiViewRequested($field, $requestedAssetIds) {
        echo '<tr data-num-collapsed="0" data-name="'.$field->name.'" data-fieldset-path="';
        $path = explode('.', $field->name);
        array_pop($path);
        echo implode('.', $path).'"';
        if (in_array($field->id, $requestedAssetIds)) echo ' class="requested"';
        echo '><td>';
            if (!empty($field->descendantFields)) {
                echo '<a class="fieldset-collapse" onclick="toggleFieldsetCollapse(this)" data-collapsed="false"></a>';
            }
        echo '</td>';
        echo '<td>';
            $fieldPath = explode('.', $field->name);
            for ($i = 0; $i < count($fieldPath) - 1; $i++) {
                echo str_repeat('&nbsp;', 12);
            }
            echo end($fieldPath);
        echo '</td>';
        echo '<td>';
            if (!empty($field->businessTerm[0])) {
                $fieldDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $field->businessTerm[0]->termDescription)))));
                echo '<a href="/search/term/'.$field->businessTerm[0]->termId.'">'.$field->businessTerm[0]->term.'</a>';
                echo '<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$fieldDef.'" class="info"><img src="/img/iconInfo.png"></div>';
            }
        echo '</td>';
        echo '<td style="white-space:nowrap;">';
            if (!empty($field->businessTerm[0])) {
                $classification = $field->businessTerm[0]->termClassification;
                switch($classification) {
                    case 'Public':
                    case '1 - Public':
                        $classificationTitle = 'Public';
                        $classification = 'Public';
                        break;
                    case 'Internal':
                    case '2 - Internal':
                        $classificationTitle = 'Internal';
                        $classification = 'Internal';
                        break;
                    case 'Confidential':
                    case '3 - Confidential':
                        $classificationTitle = 'Confidential';
                        $classification = 'Classified';
                        break;
                    case 'Highly Confidential':
                    case '4 - Highly Confidential':
                        $classificationTitle = 'Highly Confidential';
                        $classification = 'HighClassified';
                        break;
                    case 'Not Applicable':
                    case '0 - N/A':
                        $classificationTitle = 'Not Applicable';
                        $classification = 'NotApplicable';
                        break;
                    default:
                        $classificationTitle = 'Unspecified';
                        $classification = 'NoClassification2';
                        break;
                }
                echo '<img class="classIcon" src="/img/icon'.$classification.'.png">&nbsp;'.$classificationTitle;

                if ($field->businessTerm[0]->approvalStatus != 'Approved') {
                    echo '&nbsp;&nbsp;<img class="pendingApprovalIcon" src="/img/alert.png" onmouseover="displayPendingApproval(this)" onmouseout="hidePendingAproval()">';
                }
            }
        echo '</td><td>';
            if (!empty($field->businessTerm[0])) {
                echo '<a href="/search/listTerms/'.$field->businessTerm[0]->termVocabularyId.'">'.$field->businessTerm[0]->termCommunityName.'</a>';
            }
        echo '</td></tr>';

        if (!empty($field->descendantFields)) {
            foreach ($field->descendantFields as $field) {
                $this->printApiViewRequested($field, $requestedAssetIds);
            }
        }
    }

    public function printApiAdminUpdate($field, &$index, $glossaries) {
        echo '<tr id="tr'.$index.'"><td>';
            $fieldPath = explode('.', $field->name);
            for ($i = 0; $i < count($fieldPath) - 1; $i++) {
                echo str_repeat('&nbsp;', 12);
            }
            echo end($fieldPath);
        echo '</td>';
        echo '<td>';
            if (empty($field->businessTerm[0])) {
                echo '<input type="hidden" name="data[Api][elements]['.$index.'][id]" value="'.$field->id.'" id="ApiElements'.$index.'Id">'.
                     '<input type="hidden" name="data[Api][elements]['.$index.'][name]" class="data-label" data-index="'.$index.'" value="'.$field->name.'" id="ApiElements'.$index.'Name">'.
                     '<input type="hidden" name="data[Api][elements]['.$index.'][business_term]" class="bt" data-index="'.$index.'" id="ApiElements'.$index.'BusinessTerm">'.
                     '<div class="term-wrapper display-loading" id="ApiElements'.$index.'SearchCell">'.
                        '<input type="text" class="bt-search" data-index="'.$index.'" data-default-search="'.str_replace('_', ' ', end($fieldPath)).'" placeholder="Search for a term"></input>'.
                        '<div class="selected-term"><span class="term-name"></span>  <span class="edit-opt" data-index="'.$index.'" title="Select new term"></span></div>'.
                        '<div class="loading">Loading...</div>'.
                     '</div>';
            } else {
                echo '<input type="hidden" name="data[Api][elements]['.$index.'][id]" value="'.$field->id.'" id="ApiElements'.$index.'Id">'.
                     '<input type="hidden" name="data[Api][elements]['.$index.'][name]" class="data-label" data-index="'.$index.'" value="'.$field->name.'" id="ApiElements'.$index.'Name"	data-pre-linked="true" data-orig-context="'.$field->businessTerm[0]->termCommunityName.'" data-orig-id="'.$field->businessTerm[0]->termId.'" data-orig-name="'.$field->businessTerm[0]->term.'" data-orig-def="'.preg_replace('/"/', '&quot;', $field->businessTerm[0]->termDescription).'">'.
                     '<input type="hidden" name="data[Api][elements]['.$index.'][previous_business_term]" value="'.$field->businessTerm[0]->termId.'">'.
                     '<input type="hidden" name="data[Api][elements]['.$index.'][previous_business_term_relation]" value="'.$field->businessTerm[0]->termRelationId.'">'.
                     '<input type="hidden" name="data[Api][elements]['.$index.'][business_term]" value="'.$field->businessTerm[0]->termId.'" class="bt" data-index="'.$index.'" id="ApiElements'.$index.'BusinessTerm" data-orig-term="'.$field->businessTerm[0]->termId.'">'.
                     '<div class="term-wrapper" id="ApiElements'.$index.'SearchCell">'.
                        '<input type="text" class="bt-search" data-index="'.$index.'" data-default-search="'.str_replace('_', ' ', end($fieldPath)).'" placeholder="Search for a term"></input>'.
                        '<div class="selected-term"><span class="term-name">'.$field->businessTerm[0]->term.'</span>  <span class="edit-opt" data-index="'.$index.'" title="Select new term"></span></div>'.
                        '<div class="loading">Loading...</div>'.
                     '</div>';
            }
            echo '<input type="text" name="data[Api][elements]['.$index.'][propName]" class="bt-new-name" id="ApiElements'.$index.'PropName" data-index="'.$index.'" placeholder="Proposed name for the term"></input>'.
             '</td><td>'.
                 '<input type="checkbox" name="data[Api][elements]['.$index.'][new]" id="ApiElements'.$index.'New" class="new-check" data-index="'.$index.'">'.
             '</td><td class="glossary-cell">'.
                 '<div class="view-context'.$index.'" style="white-space: nowrap"></div>'.
                 '<select name="data[Api][elements]['.$index.'][propGlossary]" class="bt-new-glossary" id="ApiElements'.$index.'PropGlossary">'.
                 '<option value="">Select a glossary</option>'.
                 '<option value="">I don\'t know</option>';
                     foreach ($glossaries as $glossary) {
                         echo '<option value="'.$glossary->glossaryId.'">'.$glossary->glossaryName.'</option>';
                     }
            echo '</select>'.
             '</td><td>'.
                 '<div id="view-definition'.$index.'" class="view-definition"></div>'.
                 '<textarea name="data[Api][elements]['.$index.'][propDefinition]" class="bt-new-definition" id="ApiElements'.$index.'PropDefinition" placeholder="Propose a definition for the term" rows="1" style="width:100%;"></textarea>'.
             '</td>';
        echo '</tr>';
        $index++;

        if (!empty($field->descendantFields)) {
            foreach ($field->descendantFields as $field) {
                $this->printApiAdminUpdate($field, $index, $glossaries);
            }
        }
    }
}
