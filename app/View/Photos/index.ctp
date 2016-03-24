<?php
    $baseUrl = $this->Html->url(['?' => ['limit' => $limit, 'offset' => 'OFFSET']]);
?>
<?= $this->Html->script('resemble') ?>
<div style="position: absolute; left: -9999px; width: 50px">
    <img style="width: 100%; height: auto" class="emptyphoto" src="<?= $this->Html->url(['action' => 'view', 'this is not a real netID']) ?>">
</div>
<style type="text/css">
    th { text-align: left}
    a.btn-current { background-color: white}
    td.mismatch { background-color: pink }
    td.no-byu-available { background-color: #ccc }
</style>
<div class="innerLower">
    <div style="max-width: 900px; width: 100%; margin: 0 auto">
        <table class="form userlist" style="margin: 0 auto">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>BYU</th>
                    <th style="width: 1%"></th>
                    <th>Collibra</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $idx => $user): ?>
                    <tr>
                        <td><?= $user->UserFirstName ?></td>
                        <td><?= $user->UserLastName ?></td>
                        <td><?= $user->UserUserName ?></td>
                        <td><?= $user->UserEmailaddress ?></td>
                        <td width="50px">
                            <img
                                style="width: 100%; height: auto"
                                class="matchy matchy-byu"
                                src="<?= $this->Html->url(['action' => 'view', $user->UserUserName]) ?>"
                                data-net-id="<?= h($user->UserUserName) ?>"
                                data-source="BYU">
                        </td>
                        <td>
                            <a
                                href="#"
                                style="display: none"
                                data-row-index="<?= $idx ?>"
                                data-net-id="<?= h($user->UserUserName) ?>"
                                class="copy-photo btn">&rArr;</a>
                        </td>
                        <td width="50px">
                            <img
                                style="width: 100%; height: auto"
                                class="matchy matchy-collibra collibraphoto-<?= $idx ?>"
                                src="<?= $this->Html->url(['action' => 'rview', $user->UserId]) ?>"
                                data-orig-src="<?= $this->Html->url(['action' => 'rview', $user->UserId]) ?>"
                                data-net-id="<?= h($user->UserUserName) ?>"
                                data-source="Collibra">
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php if ($total > count($users)): ?>
            <?php $i = 0; ?>
            <br>
            <a class="btn" href="<?= ($offset == 0) ? 'javascript:void(0)' : str_replace('OFFSET', $offset - $limit, $baseUrl) ?>">&lt;</a>
            <?php while ($i < $total): ?>
                <?php if ($i == $offset): ?>
                    <a href="javascript:void(0)" class="btn btn-current"><?= ($i / $limit) + 1 ?></a>
                <?php else: ?>
                    <a href="<?= str_replace('OFFSET', $i, $baseUrl) ?>" class="btn"><?= ($i / $limit) + 1 ?></a>
                <?php endif ?>
                <?php $i += $limit; ?>
            <?php endwhile ?>
            <a class="btn" href="<?= ($offset + $limit > $total) ? 'javascript:void(0)' : str_replace('OFFSET', $offset + $limit, $baseUrl) ?>">&gt;</a>
        <?php endif ?>
    </div>
</div>
<div style="padding-bottom: 50px">&nbsp;</div>
<script type="text/javascript">
    var loadedImg = {};
    var emptyPhotoLoaded = false;
    var emptyCheckBacklog = [];
    var emptyPhoto = null;

    $(document).ready(function() {
        $('table.userlist').on('click', 'a.copy-photo', function(evt) {
            evt.preventDefault();
            var $this = $(this);
            var netId = $this.data('net-id');
            var rowIndex = $this.data('row-index');
            var $img = $('img.collibraphoto-' + rowIndex);

            $img.hide();
            $.post("<?= $this->Html->url(['action' => 'update']) ?>", {netId: netId})
                .done(function(data) {
                    if (data.success && data.success === "true") {
                        refreshPhoto(rowIndex);
                    }
                })
                .fail(function(data) {
                    //Maybe whine?
                })
                .always(function() {
                    $img.show();
                });
        });

        function refreshPhoto(idx) {
            var $img = $('img.collibraphoto-' + idx);
            var origSrc = $img.data('orig-src');
            var d = new Date();
            $img.attr('src', origSrc + '?' + d.getTime());
        }

        $('img.matchy').each(function() {
            $(this).on('load', function(e) { imageLoaded(e.target); })
            if (this.complete) {
                imageLoaded(this);
            }
        });

        $('img.emptyphoto').each(function() {
            if (this.complete) {
                processEmptyCheckBacklog();
            } else {
                $(this).one('load', processEmptyCheckBacklog);
            }
        });

        function processEmptyCheckBacklog() {
            emptyPhoto = getImageData($('img.emptyphoto')[0]);
            emptyPhotoLoaded = true;
            emptyCheckBacklog.forEach(function (netId) {
                comparePhotos(netId);
            });
        }

        function imageLoaded(img) {
            var $img = $(img);
            var netId = $img.data('net-id');
            var source = $img.data('source');
            if (!loadedImg[netId]) {
                loadedImg[netId] = {};
            }
            loadedImg[netId][source] = true;

            if (loadedImg[netId]['BYU'] && loadedImg[netId]['Collibra']) {
                comparePhotos(netId);
            }
        }

        function comparePhotos(netId) {
            if (!emptyPhotoLoaded) {
                emptyCheckBacklog.push(netId);
                return;
            }
            var byuImg = $('img.matchy-byu[data-net-id="' + netId + '"]');
            var byu = getImageData(byuImg[0]);
            var collibra = getImageData($('img.matchy-collibra[data-net-id="' + netId + '"]')[0]);
            resemble(emptyPhoto).compareTo(byu).onComplete(function(data) {
                if (data.rawMisMatchPercentage < 1) {
                    byuImg.closest('tr').find('td').addClass('no-byu-available');
                    byuImg.closest('tr').find('a.copy-photo').hide();
                } else {
                    resemble(byu).compareTo(collibra).onComplete(function(data) {
                        if (data.rawMisMatchPercentage && data.rawMisMatchPercentage > 2) {
                            byuImg.closest('tr').find('td').addClass('mismatch');
                            byuImg.closest('tr').find('a.copy-photo').show();
                        } else {
                            byuImg.closest('tr').find('td').removeClass('mismatch');
                            byuImg.closest('tr').find('a.copy-photo').hide();
                        }
                    });
                }
            });
        }

        function getImageData(img) {
            // Create an empty canvas element
            var canvas = document.createElement("canvas");
            canvas.width = img.width;
            canvas.height = img.height;

            // Copy the image contents to the canvas
            var ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, img.width, img.height);
            return ctx.getImageData(0, 0, img.width, img.height);
        }
});
</script>