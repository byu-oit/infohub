<div class="autoComplete">
	<ul class="results"></ul>
	<span>Common Searches</span>
    <ul>
        <?php
            foreach($commonSearches as $search){
                echo '<li>'.$search.'</li>';
            }
        ?>
	</ul>
</div>