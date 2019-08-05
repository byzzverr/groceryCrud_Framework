<?
/**
 * This is a bootstrapped table, you should pass along the following
 * array to this view to render it properly.
 * It can be any array as long as it as descriptive keys.
 * NB! remember the array always needs an "id" field.
 */
?>

<div class="container">
   <div class="page-header">
     <h1><?php echo humanize($page_title); ?></h1>
   </div>
   <div class="row">
    <table class="table table-condensed">
      <thead>
        <tr>
   <? 
   if (isset($controls)) { $results[0]['controls'] = '1'; }
   foreach ($results[0] as $key => $result){ 
          echo "<th>".humanize($key)."</th>";
   } ?>
        </tr>
      </thead>
      <tbody>
   <? foreach ($results as $result){ 
    if (isset($controls)) { $result['controls'] = '1'; }
      echo '<tr>';
      foreach ($result as $field_name => $value) {
        if (isset($controls) && $field_name == 'controls') { 
          echo "<td>"; 
          foreach ($controls as $action => $url) {
            echo ' <a href="'.$url.$result['id'].'">'.$action.'</a> ';
          }
          echo "</td>"; 
        }else{
          echo "<td>$value</td>"; 
        }
      }
      echo '</tr>';
   } ?>
      </tbody>
    </table>
   <div class="form-actions">
    <a onclick="history.go(-1)" class="btn">Back</a>
   </div>
   </div>
</div>