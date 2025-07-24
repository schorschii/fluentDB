<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

if(empty($_GET['query'])) {
	die('<div class="alert info nomargin">'.LANG('please_enter_a_search_term').'</div>');
}

$maxResults = 5;
$more = false;
if(!empty($_GET['context']) && $_GET['context'] === 'more') {
	$maxResults = 99999999;
	$more = true;
}

$items = [];
$moreAvail = false;
$counter = 0;
foreach($db->searchAllObject($_GET['query']) as $o) {
	$counter ++;
	#if(!$cl->checkPermission($o, PermissionManager::METHOD_READ, false)) continue; // TODO
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = $o;
}

// extension search
foreach($ext->getAggregatedConf('frontend-search-function') as $func) {
	$counter = 0;
	foreach(call_user_func($func, $_GET['query'], $cl, $db) as $sr) {
		if(!$sr instanceof Models\SearchResult) continue;
		$counter ++;
		if($counter > $maxResults) { $moreAvail = true; break; }
		$items[] = $sr;
	}
}

if(count($items) == 0) {
	die('<div class="alert warning nomargin">'.LANG('no_search_results').'</div>');
} elseif(!$more && count($items) > 25) {
	$items = array_chunk($items, 25)[0];
}
?>

<?php if($more) { ?>

<h2><?php echo str_replace('%s', htmlspecialchars($_GET['query']), LANG('search_results_for')); ?></h2>
<div class='details-abreast'>
	<div class='stickytable'>
		<table class='list searchable sortable savesort fullwidth'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('object_type'); ?></th>
					<th class='searchable sortable'><?php echo LANG('title'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($items as $item) { ?>
				<tr>
					<td>
						<?php if($item->object_type_image) { ?><img src='<?php echo base64image($item->object_type_image); ?>'><?php } ?>
						<?php echo htmlspecialchars($item->object_type_title); ?>
					</td>
					<td>
						<a onkeydown='handleSearchResultNavigation(event)' <?php echo Html::explorerLink('views/object.php?id='.$item->id); ?>><?php echo htmlspecialchars($item->title.($o->category_field_id!=1 ? ' ('.LANG($o->category_field_title).')' : '')); ?></a>
					</td>
				</tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
							</div>
							<div class='controls'>
								<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<?php } else { ?>

<?php foreach($items as $item) { ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo Html::explorerLink('views/object.php?id='.$item->id, 'closeSearchResults()'); ?>>
			<?php if($item->object_type_image) { ?><img src='<?php echo base64image($item->object_type_image); ?>'><?php } ?>
			<?php echo htmlspecialchars($item->title.($o->category_field_id!=1 ? ' ('.LANG($o->category_field_title).')' : '')); ?>
		</a>
	</div>
<?php } ?>
<?php if($moreAvail) { ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo Html::explorerLink('views/search.php?context=more&query='.urlencode($_GET['query']), 'closeSearchResults()'); ?>><img src='img/eye.dyn.svg'><?php echo LANG('more'); ?></a>
	</div>
<?php } ?>

<?php } ?>
