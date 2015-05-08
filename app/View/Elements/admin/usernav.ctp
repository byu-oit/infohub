<h2>Users</h2>
<ul id="userList">
   <li><a class="add-record" href="/admin/adduser">+ Add User</a></li>
    <?php
        foreach($users as $user){
            $user = $user['CmsUsers'];
            echo '<li><a href="/admin/edituser/'.$user['id'].'">'.$user['username'].'</a></li>';
        }
    ?>
</ul>