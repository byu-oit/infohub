<?php

App::uses('AppHelper', 'View/Helper');

class FieldsetHelper extends AppHelper {
    public function printApiView($term) {
        echo '<tr><td>';
            if (!empty($term->businessTerm[0])) {
                echo '<input type="checkbox"'.
                     ' data-title="'.h($term->businessTerm[0]->term).'"'.
                     ' data-vocabID="'.h($term->businessTerm[0]->termCommunityId).'"'.
                     ' value="'.h($term->businessTerm[0]->termId).'"'.
                     ' class="chk'; if ($term->assetType == 'Fieldset') echo ' fieldset'; echo '"'.
                     ' id="chk'.h($term->businessTerm[0]->termId).'"'.
                     ' checked="checked"'.
                     ' data-name="'.$term->name.'"'.
                     ' data-fieldset-path="';
                     $path = explode('.', $term->name);
                     array_pop($path);
                     echo implode('.', $path).'">';
            } else {
                echo '<input type="checkbox"'.
                     ' data-title="'.$term->name.'"'.
                     ' data-vocabID=""'.
                     ' value=""'.
                     ' class="chk'; if ($term->assetType == 'Fieldset') echo ' fieldset'; echo '"'.
                     ' checked="checked"'.
                     ' data-name="'.$term->name.'"'.
                     ' data-fieldset-path="';
                     $path = explode('.', $term->name);
                     array_pop($path);
                     echo implode('.', $path).'">';
            }
        echo '</td>';
        echo '<td>';
            $termPath = explode('.', $term->name);
            for ($i = 0; $i < count($termPath) - 1; $i++) {
                echo str_repeat('&nbsp;', 12);
            }
            echo end($termPath);
        echo '</td>';
        echo '<td>';
            if (!empty($term->businessTerm[0])) {
                $termDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $term->businessTerm[0]->termDescription)))));
                echo '<a href="/search/term/'.$term->businessTerm[0]->termId.'">'.$term->businessTerm[0]->term.'</a>';
                echo '<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$termDef.'" class="info"><img src="/img/iconInfo.png"></div>';
            }
        echo '</td>';
        echo '<td style="white-space:nowrap;">';
            if (!empty($term->businessTerm[0])) {
                $classification = $term->businessTerm[0]->termClassification;
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

                if ($term->businessTerm[0]->approvalStatus != 'Approved') {
                    echo '&nbsp;&nbsp;<img class="pendingApprovalIcon" src="/img/alert.png" onmouseover="displayPendingApproval(this)" onmouseout="hidePendingAproval()">';
                }
            }
        echo '</td></tr>';

        if (!empty($term->descendantFields)) {
            foreach ($term->descendantFields as $field) {
                $this->printApiView($field);
            }
        }
    }

    public function printApiAdminUpdate($term, &$index) {
        echo '<tr><td>';
            $termPath = explode('.', $term->name);
            for ($i = 0; $i < count($termPath) - 1; $i++) {
                echo str_repeat('&nbsp;', 12);
            }
            echo end($termPath);
        echo '</td>';
        if (empty($term->businessTerm[0])) {
            echo '<td>';
                echo '<input type="hidden" name="data[Api][elements]['.$index.'][id]" value="'.$term->id.'" id="ApiElements'.$index.'Id">'.
                     '<input type="hidden" name="data[Api][elements]['.$index.'][name]" class="data-label" data-index="'.$index.'" value="'.$term->name.'" id="ApiElements'.$index.'Name">'.
                     '<input type="hidden" name="data[Api][elements]['.$index.'][business_term]" class="bt" data-index="'.$index.'" id="ApiElements'.$index.'BusinessTerm">'.
                     '<div class="term-wrapper display-loading" id="ApiElements'.$index.'SearchCell">'.
                        '<input type="text" class="bt-search" data-index="'.$index.'" placeholder="Search for a term"></input>'.
                        '<div class="selected-term"><span class="term-name"></span>  <span class="edit-opt" data-index="'.$index.'" title="Select new term"></span></div>'.
                        '<div class="loading">Loading...</div>'.
                     '</div>';
            echo '</td>'.
                 '<td class="view-context'.$index.'" style="white-space: nowrap"></td>'.
                 '<td id="view-definition'.$index.'" class="view-definition"></td>';
        } else {
            echo '<td>'.
                 '<input type="hidden" name="data[Api][elements]['.$index.'][id]" value="'.$term->id.'" id="ApiElements'.$index.'Id">'.
                 '<input type="hidden" name="data[Api][elements]['.$index.'][name]" class="data-label" data-index="'.$index.'" value="'.$term->name.'" id="ApiElements'.$index.'Name"	data-pre-linked="true" data-orig-context="'.$term->businessTerm[0]->termCommunityName.'" data-orig-id="'.$term->businessTerm[0]->termId.'" data-orig-name="'.$term->businessTerm[0]->term.'" data-orig-def="'.preg_replace('/"/', '&quot;', $term->businessTerm[0]->termDescription).'">'.
                 '<input type="hidden" name="data[Api][elements]['.$index.'][previous_business_term]" value="'.$term->businessTerm[0]->termId.'">'.
                 '<input type="hidden" name="data[Api][elements]['.$index.'][previous_business_term_relation]" value="'.$term->businessTerm[0]->termRelationId.'">'.
                 '<input type="hidden" name="data[Api][elements]['.$index.'][business_term]" value="'.$term->businessTerm[0]->termId.'" class="bt" data-index="'.$index.'" id="ApiElements'.$index.'BusinessTerm" data-orig-term="'.$term->businessTerm[0]->termId.'">'.
                 '<div class="term-wrapper" id="ApiElements'.$index.'SearchCell">'.
                    '<input type="text" class="bt-search" data-index="'.$index.'" placeholder="Search for a term"></input>'.
                    '<div class="selected-term"><span class="term-name">'.$term->businessTerm[0]->term.'</span>  <span class="edit-opt" data-index="'.$index.'" title="Select new term"></span></div>'.
                    '<div class="loading">Loading...</div>'.
                 '</div>';
            echo '</td>'.
                 '<td class="view-context'.$index.'" style="white-space: nowrap"></td>'.
                 '<td id="view-definition'.$index.'" class="view-definition"></td>';
        }
        echo '</tr>';
        $index++;

        if (!empty($term->descendantFields)) {
            foreach ($term->descendantFields as $field) {
                $this->printApiAdminUpdate($field, $index);
            }
        }
    }

    public function printApiAdminProposeTerms($term) {
        if (empty($term->businessTerm)) {
            echo '<tr field-id="'.$term->id.'" field-name="'.$term->name.'">';
                echo '<td><input type="checkbox"></td>'.
                     '<td>';
                        $termPath = explode('.', $term->name);
                        for ($i = 0; $i < count($termPath) - 1; $i++) {
                            echo str_repeat('&nbsp;', 12);
                        }
                        echo end($termPath);
                echo '</td>';
                echo '<td>';
                    $name = end($termPath);
                    $name = ucwords(str_replace("_", " ", $name));
                    echo '<input type="text" class="proposed-name" value="'.$name.'">';
                echo '</td>';
                echo '<td></td>';
                echo '<td>';
                    echo '<input type="text" class="proposed-def" placeholder="">';
                echo '</td>';
            echo '</tr>';
        }

        if (!empty($term->descendantFields)) {
            foreach ($term->descendantFields as $field) {
                $this->printApiAdminProposeTerms($field);
            }
        }
    }
}
