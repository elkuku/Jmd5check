<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 15.07.15
 * Time: 23:01
 */

?>
<p>
	This wil check ... blabla
</p>

<h3>Your version: <?= $this->version ?></h3>

<?php if (!$this->hashFile) : ?>
<h4>The hash file has not been found (yet)</h4>
<a class="btn btn-default btn-primary" href="<?= $this->downloadLink ?>">Download hash file</a>
<?php else : ?>
	<p>Hash file exists</p>
	<a class="btn btn-default btn-primary" href="<?= $this->checkLink ?>">Check Joomla! CMS <?= $this->version ?></a>
<?php endif; ?>
