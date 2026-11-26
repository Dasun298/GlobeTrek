<?php
require_once __DIR__ . '/_common.php';
// Each table/column identifier comes from this fixed whitelist: no dynamic user-supplied SQL identifiers.
$catalog = [
  'packages' => [
      'title'=>'Tour Packages','singular'=>'Tour Package','name'=>'package_name','image'=>true,'hotel_image'=>false,
      'fields'=>[
          'package_name'=>['label'=>'Package name','limit'=>200,'required'=>true],
          'destination'=>['label'=>'Destination','limit'=>200,'required'=>true],
          'price'=>['label'=>'Package price (Rs.)','type'=>'price','required'=>true],
          'details'=>['label'=>'Package description','type'=>'textarea','limit'=>3000],
          'image'=>['label'=>'Image filename','type'=>'image']
      ],'reference'=>['bookings','package_id']
  ],
  'hotels' => [
      'title'=>'Hotels','singular'=>'Hotel','name'=>'hotel_name','image'=>true,'hotel_image'=>true,
      'fields'=>[
          'hotel_name'=>['label'=>'Hotel name','limit'=>100,'required'=>true],
          'location'=>['label'=>'Location','limit'=>100,'required'=>true],
          'room_type'=>['label'=>'Room type','limit'=>100],
          'price'=>['label'=>'Listed room price (Rs.)','type'=>'price','required'=>true],
          'status'=>['label'=>'Availability','type'=>'select','values'=>['Available','Unavailable']],
          'image'=>['label'=>'Image filename','type'=>'image']
      ],'reference'=>['hotel_bookings','hotel_id']
  ],
  'transport'=> [
      'title'=>'Hotel Transport','singular'=>'Hotel vehicle','name'=>'vehicle_name','image'=>false,
      'fields'=>[
          'vehicle_name'=>['label'=>'Vehicle name','limit'=>100,'required'=>true],
          'vehicle_type'=>['label'=>'Vehicle type','limit'=>100,'required'=>true],
          'vehicle_number'=>['label'=>'Registration number','limit'=>100,'required'=>true],
          'destination'=>['label'=>'Destination','limit'=>100],
          'price'=>['label'=>'Transport price (Rs.)','type'=>'price','required'=>true],
          'status'=>['label'=>'Availability','type'=>'select','values'=>['Available','Ready','Reserved','Booked','Unavailable']]
      ],'reference'=>['hotel_bookings','transport_id']
  ],
  'transportation'=> [
      'title'=>'Tour Transportation','singular'=>'Tour vehicle','name'=>'vehicle','image'=>false,
      'fields'=>[
          'vehicle'=>['label'=>'Vehicle name','limit'=>100,'required'=>true],
          'destination'=>['label'=>'Destination','limit'=>100,'required'=>true],
          'price'=>['label'=>'Price (Rs.)','type'=>'price','required'=>true],
          'status'=>['label'=>'Status','type'=>'select','values'=>['Available','Ready','Reserved','Booked','Unavailable']]
      ],'reference'=>null
  ]
];
$type = getv('type');
if (!isset($catalog[$type])) $type = 'packages';
$def = $catalog[$type];
$fields = $def['fields'];
$nameCol = $def['name'];
$back = 'catalog.php?type=' . rawurlencode($type);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_admin_post();
        $action = postv('action');
        $id = recid(postv('id'));
        if ($action === 'delete') {
            if (!$id) throw new RuntimeException('Invalid catalog item.');
            if ($def['reference']) {
                [$refTable,$refField] = $def['reference'];
                if ((int)scalar_db("SELECT COUNT(*) FROM `$refTable` WHERE `$refField`=?",'i',[$id]) > 0) {
                    throw new RuntimeException('This item is used by existing bookings. Edit it instead of deleting it.');
                }
            }
            if (exec_db("DELETE FROM `$type` WHERE id=?",'i',[$id]) !== 1) throw new RuntimeException('Item not found.');
            flash($def['singular'] . ' deleted.');
        } elseif ($action === 'save') {
            $old = $id ? fetch_one_db("SELECT * FROM `$type` WHERE id=?",'i',[$id]) : null;
            if ($id && !$old) throw new RuntimeException('The record you are editing no longer exists.');
            $values = [];
            foreach ($fields as $column => $config) {
                $kind = $config['type'] ?? 'text';
                if ($kind === 'image') {
                    $newImage = save_image_upload('image_upload', !empty($def['hotel_image']));
                    // A previously saved image is retained until a new one is uploaded.
                    $value = $newImage ?? (string)($old[$column] ?? '');
                } elseif ($kind === 'price') {
                    $input = postv($column);
                    // Accept legacy transportation amounts shown as "Rs. 5000" and normalize.
                    $clean = preg_replace('/^Rs\.?\s*/i','',$input);
                    $clean = str_replace(',', '', $clean);
                    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/',$clean) || (float)$clean <= 0) throw new RuntimeException('Enter a positive valid price, such as 5000 or 5000.00.');
                    $value = number_format((float)$clean,2,'.','');
                } else {
                    $value = postv($column);
                    if (!empty($config['required']) && $value === '') throw new RuntimeException('Please fill in ' . $config['label'] . '.');
                    if (strlen($value) > ($config['limit'] ?? 100)) throw new RuntimeException('Too many characters: ' . $config['label']);
                    if ($kind === 'select' && !in_array($value,$config['values'],true)) throw new RuntimeException('Choose a valid ' . $config['label'] . '.');
                }
                $values[] = $value;
            }
            $columns = array_keys($fields);
            if ($id) {
                $set = implode(', ', array_map(static fn($c) => "`$c`=?", $columns));
                exec_db("UPDATE `$type` SET $set WHERE id=?",str_repeat('s',count($columns)).'i',[...$values,$id]);
                flash($def['singular'] . ' updated successfully.');
            } else {
                $columnsSql = implode(', ',array_map(static fn($c) => "`$c`",$columns));
                $placeholders = implode(', ',array_fill(0,count($columns),'?'));
                exec_db("INSERT INTO `$type` ($columnsSql) VALUES ($placeholders)",str_repeat('s',count($columns)),$values);
                flash($def['singular'] . ' created successfully.');
            }
        } else { throw new RuntimeException('Invalid catalog action.'); }
        redirect_to($back);
    } catch (Throwable $e) { admin_error($e,$back); }
}
$editId = recid(getv('edit'));
$editing = $editId ? fetch_one_db("SELECT * FROM `$type` WHERE id=?",'i',[$editId]) : null;
$search = substr(getv('search'),0,80); $like='%'.$search.'%';
$destinationCol = in_array($type,['packages','transport','transportation'],true) ? 'destination' : 'location';
$records = fetch_all_db("SELECT * FROM `$type` WHERE (?='' OR `$nameCol` LIKE ? OR `$destinationCol` LIKE ?) ORDER BY id DESC LIMIT 200",'sss',[$search,$like,$like]);
page_start($def['title'],$type,'Add, update and review ' . strtolower($def['title']) . ' without opening old Staff pages.');
?>
<div class="tag-nav"><a class="<?= $type==='packages'?'active':'' ?>" href="catalog.php?type=packages">✈ Tour Packages</a><a class="<?= $type==='hotels'?'active':'' ?>" href="catalog.php?type=hotels">⌂ Hotels</a><a class="<?= $type==='transport'?'active':'' ?>" href="catalog.php?type=transport">🚐 Hotel Transport</a><a class="<?= $type==='transportation'?'active':'' ?>" href="catalog.php?type=transportation">↗ Tour Transportation</a></div>
<section class="panel"><div class="panel-head"><div><p class="eyebrow"><?= $editing?'EDIT RECORD':'CREATE NEW' ?></p><h2><?= h($editing?'Edit '.$def['singular']:'Add '.$def['singular']) ?></h2></div><?php if ($editing): ?><a class="btn btn-muted" href="<?= h($back) ?>">+ New Item</a><?php endif; ?></div>
<form class="admin-form-grid" action="<?= h($back) ?>" method="post" enctype="multipart/form-data"><?php csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
<?php foreach ($fields as $column=>$config): $kind=$config['type']??'text'; ?>
<div class="form-field <?= $kind==='textarea'?'wide-field':'' ?>"><label for="field-<?= h($column) ?>"><?= h($config['label']) ?><?= !empty($config['required']) ? ' *' : '' ?></label>
<?php if ($kind === 'textarea'): ?><textarea id="field-<?= h($column) ?>" name="<?= h($column) ?>" rows="4" maxlength="<?= (int)($config['limit']??3000) ?>"><?= h($editing[$column]??'') ?></textarea>
<?php elseif ($kind === 'select'): ?><select id="field-<?= h($column) ?>" name="<?= h($column) ?>" required><?php foreach ($config['values'] as $choice): ?><option value="<?= h($choice) ?>" <?= ($editing[$column]??'Available')===$choice?'selected':'' ?>><?= h($choice) ?></option><?php endforeach; ?></select>
<?php elseif ($kind === 'image'): ?><input id="field-<?= h($column) ?>" type="file" name="image_upload" accept="image/jpeg,image/png,image/webp,image/avif" data-image-preview="imagePreview"><div class="form-help">Upload a JPG, PNG, WEBP or AVIF (max 5MB). <?= !empty($editing[$column]) ? 'Existing: '.h($editing[$column]):'No image selected.' ?></div><img id="imagePreview" class="form-image-preview" src="<?= h(admin_image_url($editing[$column]??'', !empty($def['hotel_image']))) ?>" alt="Image preview" <?= admin_image_url($editing[$column]??'', !empty($def['hotel_image'])) ? '' : 'hidden' ?>>
<?php elseif ($kind === 'price'): ?><input id="field-<?= h($column) ?>" name="<?= h($column) ?>" type="text" inputmode="decimal" placeholder="e.g. 5000.00" maxlength="25" value="<?= h($editing[$column]??'') ?>" required>
<?php else: ?><input id="field-<?= h($column) ?>" name="<?= h($column) ?>" type="text" maxlength="<?= (int)($config['limit']??100) ?>" value="<?= h($editing[$column]??'') ?>" <?= !empty($config['required'])?'required':'' ?>><?php endif; ?></div>
<?php endforeach; ?>
<div class="form-actions"><button class="btn btn-primary" type="submit"><?= $editing?'Save Changes':'Add '.$def['singular'] ?> ↗</button><a class="btn btn-muted" href="<?= h($back) ?>">Clear</a></div></form><p class="panel-note">For package/hotel images, the file is saved to the existing GlobeTrek uploads directory. Records referenced by bookings cannot be deleted.</p></section>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">CURRENT CATALOG</p><h2>Existing <?= h($def['title']) ?></h2></div><span class="count-chip"><?= count($records) ?> shown</span></div><form method="get" class="filter-bar"><input type="hidden" name="type" value="<?= h($type) ?>"><label class="search-group">Search<input type="search" maxlength="80" name="search" placeholder="Name or destination" value="<?= h($search) ?>"></label><button class="btn btn-primary" type="submit">Search</button><a href="<?= h($back) ?>" class="btn btn-muted">Reset</a></form>
<?php if (!$records): ?><div class="empty-state">No matching records in this catalog.</div><?php endif; ?>
<?php if (!empty($def['image'])): ?><div class="catalog-grid">
<?php foreach ($records as $r): $img=admin_image_url($r['image']??'',!empty($def['hotel_image'])); $where=$r[$destinationCol]??''; ?><article class="catalog-card"><div class="catalog-photo"><?php if ($img): ?><img src="<?= h($img) ?>" alt="<?= h($r[$nameCol]) ?>" loading="lazy"><?php else: ?><div class="image-fallback">✈</div><?php endif; ?></div><div class="catalog-body"><p class="eyebrow">#<?= (int)$r['id'] ?> · <?= h($where) ?></p><h3><?= h($r[$nameCol]) ?></h3><strong class="catalog-price"><?= money($r['price']) ?></strong><?php if (isset($r['status'])): ?><?= status_badge($r['status']) ?><?php endif; ?><?php if ($type==='packages'): ?><p class="catalog-description"><?= h(function_exists('mb_substr') ? mb_substr((string)($r['details']??''),0,110) : substr((string)($r['details']??''),0,110)) ?></p><?php elseif (!empty($r['room_type'])): ?><p class="catalog-description"><?= h($r['room_type']) ?></p><?php endif; ?><div class="catalog-actions"><a class="btn btn-outline btn-sm" href="catalog.php?type=<?= h($type) ?>&amp;edit=<?= (int)$r['id'] ?>">Edit</a><form method="post" class="inline-form" action="<?= h($back) ?>" data-confirm="Delete this record? Records used by bookings cannot be deleted."><?php csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></div></div></article><?php endforeach; ?>
</div>
<?php else: ?><div class="table-scroll"><table><thead><tr><th>ID</th><th>Vehicle</th><th>Destination</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($records as $r): ?><tr><td><strong>#<?= (int)$r['id'] ?></strong></td><td><strong><?= h($r[$nameCol]) ?></strong><?php if (isset($r['vehicle_type'])): ?><small><?= h($r['vehicle_type']) ?> · <?= h($r['vehicle_number']) ?></small><?php endif; ?></td><td><?= h($r['destination']??'') ?></td><td><?= money($r['price']) ?></td><td><?= status_badge($r['status']) ?></td><td class="actions-cell"><a href="catalog.php?type=<?= h($type) ?>&amp;edit=<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm">Edit</a><form method="post" action="<?= h($back) ?>" class="inline-form" data-confirm="Delete this vehicle? Vehicles referenced by bookings cannot be deleted."><?php csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php page_end(); ?>
