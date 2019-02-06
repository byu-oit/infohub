<?php

App::uses('Component', 'Controller');

class PostComponent extends Component {
    public function preparePostData($postData, $match='/%5B[0-9]*%5D/', $replacement='') {
        $postString = http_build_query($postData);
        $postString = preg_replace($match, $replacement, $postString);
        return $postString;
    }
}
