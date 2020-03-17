<?php
if (isset($_SESSION['flashMsg'])) {
    ?><script>window.alert('<?= addslashes($_SESSION['flashMsg']) ?>')</script><?php
    unset($_SESSION['flashMsg']);
}