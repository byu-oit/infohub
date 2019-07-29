<?php

App::uses('AppHelper', 'View/Helper');

class VirtualDatasetHelper extends AppHelper {
    public function printTableView($table) {
        echo '<tr data-num-collapsed="0" data-name="'.$table->name.'" data-container-path="';
        $path = explode('.', $table->name);
        array_pop($path);
        echo implode('.', $path).'"><td>';
        echo '<a class="container-collapse" onclick="toggleContainerCollapse(this)" data-collapsed="false"></a>';
        echo '</td>';
        echo '<td>';
            echo '<input type="checkbox"'.
                 ' class="chk container"'.
                 ' data-name="'.$table->name.'"'.
                 ' data-container-path="';
                 $path = explode('.', $table->name);
                 array_pop($path);
                 echo implode('.', $path).'">';
        echo '</td>';
        echo '<td>';
            $tablePath = explode('.', $table->name);
            echo str_repeat('&nbsp;', 12 * (count($tablePath) - 2));
            echo end($tablePath);
        echo '</td>';
        echo '<td></td><td></td><td></td></tr>';

        foreach ($table->columns as $column) {
            echo '<tr data-num-collapsed="0" data-name="'.$column->columnName.'" data-container-path="';
            $path = explode('.', $column->columnName);
            array_pop($path);
            echo implode('.', $path).'"><td>';
            echo '</td>';
            echo '<td>';
                if (!empty($column->businessTerm[0])) {
                    echo '<input type="checkbox"'.
                         ' data-title="'.h($column->businessTerm[0]->term).'"'.
                         ' data-vocabID="'.h($column->businessTerm[0]->termCommunityId).'"'.
                         ' value="'.h($column->businessTerm[0]->termId).'"'.
                         ' class="chk"'.
                         ' id="chk'.h($column->businessTerm[0]->termId).'"'.
                         ' data-name="'.$column->columnName.'"'.
                         ' data-column-id="'.$column->columnId.'"'.
                         ' data-table-name="'.$table->name.'"'.
                         ' data-table-id="'.$table->id.'"'.
                         ' data-container-path="';
                         $path = explode('.', $column->columnName);
                         array_pop($path);
                         echo implode('.', $path).'">';
                } else {
                    echo '<input type="checkbox"'.
                         ' data-title="'.$column->columnName.'"'.
                         ' data-vocabID=""'.
                         ' value=""'.
                         ' class="chk"'.
                         ' data-name="'.$column->columnName.'"'.
                         ' data-column-id="'.$column->columnId.'"'.
                         ' data-table-name="'.$table->name.'"'.
                         ' data-table-id="'.$table->id.'"'.
                         ' data-container-path="';
                         $path = explode('.', $column->columnName);
                         array_pop($path);
                         echo implode('.', $path).'">';
                }
            echo '</td>';
            echo '<td>';
                $columnPath = explode('.', $column->columnName);
                for ($i = 0; $i < count($columnPath) - 2; $i++) {
                    echo str_repeat('&nbsp;', 12);
                }
                echo end($columnPath);
            echo '</td>';
            echo '<td>';
                if (!empty($column->businessTerm[0])) {
                    $columnDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $column->businessTerm[0]->termDescription)))));
                    echo '<a href="/search/term/'.$column->businessTerm[0]->termId.'">'.$column->businessTerm[0]->term.'</a>';
                    echo '<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$columnDef.'" class="info"><img src="/img/iconInfo.png"></div>';
                }
            echo '</td>';
            echo '<td style="white-space:nowrap;">';
                if (!empty($column->businessTerm[0])) {
                    $classification = $column->businessTerm[0]->termClassification;
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

                    if ($column->businessTerm[0]->approvalStatus != 'Approved') {
                        echo '&nbsp;&nbsp;<img class="pendingApprovalIcon" src="/img/alert.png" onmouseover="displayPendingApproval(this)" onmouseout="hidePendingAproval()">';
                    }
                }
            echo '</td><td>';
                if (!empty($column->businessTerm[0])) {
                    echo '<a href="/search/listTerms/'.$column->businessTerm[0]->termVocabularyId.'">'.$column->businessTerm[0]->termCommunityName.'</a>';
                }
            echo '</td></tr>';
        }
    }

    public function printTableViewRequested($table, $requestedAssetIds) {
        echo '<tr data-num-collapsed="0" data-name="'.$table->name.'" data-container-path="';
        $path = explode('.', $table->name);
        array_pop($path);
        echo implode('.', $path).'"><td>';
        echo '<a class="container-collapse" onclick="toggleContainerCollapse(this)" data-collapsed="false"></a>';
        echo '</td>';
        echo '<td>';
            $tablePath = explode('.', $table->name);
            echo str_repeat('&nbsp;', 12 * (count($tablePath) - 2));
            echo end($tablePath);
        echo '</td>';
        echo '<td></td><td></td><td></td></tr>';

        foreach ($table->columns as $column) {
            echo '<tr data-num-collapsed="0" data-name="'.$column->columnName.'" data-container-path="';
            $path = explode('.', $column->columnName);
            array_pop($path);
            echo implode('.', $path).'"';
            if (in_array($column->columnId, $requestedAssetIds)) echo ' class="requested"';
            echo '><td></td><td>';
                $columnPath = explode('.', $column->columnName);
                for ($i = 0; $i < count($columnPath) - 2; $i++) {
                    echo str_repeat('&nbsp;', 12);
                }
                echo end($columnPath);
            echo '</td>';
            echo '<td>';
                if (!empty($column->businessTerm[0])) {
                    $columnDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $column->businessTerm[0]->termDescription)))));
                    echo '<a href="/search/term/'.$column->businessTerm[0]->termId.'">'.$column->businessTerm[0]->term.'</a>';
                    echo '<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$columnDef.'" class="info"><img src="/img/iconInfo.png"></div>';
                }
            echo '</td>';
            echo '<td style="white-space:nowrap;">';
                if (!empty($column->businessTerm[0])) {
                    $classification = $column->businessTerm[0]->termClassification;
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

                    if ($column->businessTerm[0]->approvalStatus != 'Approved') {
                        echo '&nbsp;&nbsp;<img class="pendingApprovalIcon" src="/img/alert.png" onmouseover="displayPendingApproval(this)" onmouseout="hidePendingAproval()">';
                    }
                }
            echo '</td><td>';
                if (!empty($column->businessTerm[0])) {
                    echo '<a href="/search/listTerms/'.$column->businessTerm[0]->termVocabularyId.'">'.$column->businessTerm[0]->termCommunityName.'</a>';
                }
            echo '</td></tr>';
        }
    }

    public function printTableUpdate($table, &$index, $glossaries) {
        echo '<tr id="tr'.$index.'"><td>';
            $tablePath = explode('.', $table->name);
            echo str_repeat('&nbsp;', 12 * (count($tablePath) - 2));
            echo end($tablePath);
        echo '</td>';
        echo '<td></td><td></td><td></td><td></td></tr>';
        $index++;

        foreach ($table->columns as $column) {
            echo '<tr id="tr'.$index.'"><td>';
                $columnPath = explode('.', $column->columnName);
                for ($i = 0; $i < count($columnPath) - 2; $i++) {
                    echo str_repeat('&nbsp;', 12);
                }
                echo end($columnPath);
            echo '</td>';
            echo '<td>';
            if (empty($column->businessTerm[0])) {
                echo '<input type="hidden" name="data[Space][elements]['.$index.'][id]" value="'.$column->columnId.'" id="SpaceElements'.$index.'Id">'.
                     '<input type="hidden" name="data[Space][elements]['.$index.'][name]" class="data-label" data-index="'.$index.'" value="'.$column->columnName.'" id="SpaceElements'.$index.'Name">'.
                     '<input type="hidden" name="data[Space][elements]['.$index.'][business_term]" class="bt" data-index="'.$index.'" id="SpaceElements'.$index.'BusinessTerm">'.
                     '<div class="term-wrapper display-loading" id="SpaceElements'.$index.'SearchCell">'.
                        '<input type="text" class="bt-search" data-index="'.$index.'" placeholder="Search for a term"></input>'.
                        '<div class="selected-term"><span class="term-name"></span>  <span class="edit-opt" data-index="'.$index.'" title="Select new term"></span></div>'.
                        '<div class="loading">Loading...</div>'.
                     '</div>';
            } else {
                echo '<input type="hidden" name="data[Space][elements]['.$index.'][id]" value="'.$column->columnId.'" id="SpaceElements'.$index.'Id">'.
                     '<input type="hidden" name="data[Space][elements]['.$index.'][name]" class="data-label" data-index="'.$index.'" value="'.$column->columnName.'" id="SpaceElements'.$index.'Name"	data-pre-linked="true" data-orig-context="'.$column->businessTerm[0]->termCommunityName.'" data-orig-id="'.$column->businessTerm[0]->termId.'" data-orig-name="'.$column->businessTerm[0]->term.'" data-orig-def="'.preg_replace('/"/', '&quot;', $column->businessTerm[0]->termDescription).'">'.
                     '<input type="hidden" name="data[Space][elements]['.$index.'][previous_business_term]" value="'.$column->businessTerm[0]->termId.'">'.
                     '<input type="hidden" name="data[Space][elements]['.$index.'][previous_business_term_relation]" value="'.$column->businessTerm[0]->termRelationId.'">'.
                     '<input type="hidden" name="data[Space][elements]['.$index.'][business_term]" value="'.$column->businessTerm[0]->termId.'" class="bt" data-index="'.$index.'" id="SpaceElements'.$index.'BusinessTerm" data-orig-term="'.$column->businessTerm[0]->termId.'">'.
                     '<div class="term-wrapper" id="SpaceElements'.$index.'SearchCell">'.
                        '<input type="text" class="bt-search" data-index="'.$index.'" placeholder="Search for a term"></input>'.
                        '<div class="selected-term"><span class="term-name">'.$column->businessTerm[0]->term.'</span>  <span class="edit-opt" data-index="'.$index.'" title="Select new term"></span></div>'.
                        '<div class="loading">Loading...</div>'.
                     '</div>';
            }
            echo '<input type="text" name="data[Space][elements]['.$index.'][propName]" class="bt-new-name" id="SpaceElements'.$index.'PropName" data-index="'.$index.'" placeholder="Proposed name for the term"></input>'.
             '</td><td>'.
                 '<input type="checkbox" name="data[Space][elements]['.$index.'][new]" id="SpaceElements'.$index.'New" class="new-check" data-index="'.$index.'">'.
             '</td><td class="glossary-cell">'.
                 '<div class="view-context'.$index.'" style="white-space: nowrap"></div>'.
                 '<select name="data[Space][elements]['.$index.'][propGlossary]" class="bt-new-glossary" id="SpaceElements'.$index.'PropGlossary">'.
                 '<option value="">Select a glossary</option>'.
                 '<option value="">I don\'t know</option>';
                     foreach ($glossaries as $glossary) {
                         echo '<option value="'.$glossary->glossaryId.'">'.$glossary->glossaryName.'</option>';
                     }
            echo '</select>'.
             '</td><td>'.
                 '<div id="view-definition'.$index.'" class="view-definition"></div>'.
                 '<textarea name="data[Space][elements]['.$index.'][propDefinition]" class="bt-new-definition" id="SpaceElements'.$index.'PropDefinition" placeholder="Propose a definition for the term" rows="1" style="width:100%;"></textarea>'.
             '</td>';
            echo '</tr>';
            $index++;
        }
    }
}
