<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 16.07.15
 * Time: 11:56
 */
?>

<h2>Result</h2>

<h3>Checks failed</h3>

<?php if ($this->checksFailed) : ?>
	<ul>
	<?php foreach($this->checksFailed as $check) : ?>
		<li><?php echo $check ?></li>
	<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p>OK</p>
<?php endif; ?>

<h3>Checks missing</h3>

<?php if ($this->checksMissing) : ?>
	<ul>
		<?php foreach($this->checksMissing as $check) : ?>
			<li><?php echo $check ?></li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p>OK</p>
<?php endif; ?>

<h3>Checks added</h3>

<?php if ($this->checksAdd) : ?>
	<ul>
		<?php foreach($this->checksAdd as $check) : ?>
			<li><?php echo $check ?></li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p>OK</p>
<?php endif; ?>

<p>
	<a class="btn btn-default btn-primary" href="<?= $this->checkLink ?>">Check again</a>
</p>
