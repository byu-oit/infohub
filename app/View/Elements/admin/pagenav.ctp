<script src="/js/jquery-ui.min.js"></script>
<script src="/js/jquery.ui.nestedSortable.js"></script>
<script  type="text/javascript">
    function updatePageRanks(pageData){
        $.post("/admin/changerank", { "pageData":pageData },
            function(data){}
        );
    }

    $(document).ready(function(){
        $('ul#pageList').nestedSortable({
            listType: 'ul',
            disableNesting: 'no-nest',
            forcePlaceholderSize: true,
            handle: 'a',
            helper:	'clone',
            items: 'li:not(.disabled)',
            maxLevels: 2,
            placeholder: 'placeholder',
            tabSize: 25,
            tolerance: 'pointer',
            toleranceElement: '> a',
            update: function(event, ui){
                var serialized = $('ul#pageList').nestedSortable('serializeIDs');
                var doUpdate = true;
                
                var arrNav = serialized.split('||');
                for(var i=0; i<arrNav.length; i++){
                    var arrNavIDs = arrNav[i].split('::');
                    //arrNavIDs[0] = pageID
                    //arrNavIDs[1] = original parentID
                    //arrNavIDs[2] = new parentID

                    /*if(arrNavIDs[2] == 1){
                        $('ul').sortable('cancel');
                        doUpdate = false;
                        alert("Adding subpages to the home page is not allowed.");
                        break;
                    }*/
                    
                    if(arrNavIDs[1] > 0 && arrNavIDs[2] == 0){
                        doUpdate = false;
                        //$(this).sortable('cancel');
                        $('ul#pageList').nestedSortable('cancel')
                        break;
                    }

                    /*if(arrNavIDs[1] == 0 && arrNavIDs[2] > 0){
                        alert("Main level pages cannot be changed to subpages.");
                        doUpdate = false;
                        //$('ul').sortable('cancel');
                        break;
                        //if(!confirm("Are you sure you want to make this main level page a subpage?")){
                        //    doUpdate = false;
                        //    $('ul').sortable('cancel');
                        //    break;
                        //}
                    }*/
                }

                if(doUpdate){
                    updatePageRanks(serialized);
                }
            }
        });
    });
</script>

<h2>Pages</h2>
<ul id="pageList">
    <?php echo $pageList ?>
</ul>