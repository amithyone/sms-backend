<?php
\ = 'app/Services/SmsProviderService.php';
\ = file_get_contents(\);

// Comment out the price enrichment section for 5sim
\ = 'foreach (\ as &\) {
                        \ = \[" service\];
 \ = \->get5SimPricesByProduct(\, \, \);
 if (!empty(\) && isset(\[0][\cost\])) {
 \[\cost\] = (float)\[0][\cost\];
 }
 }
 unset(\);';

\ = '// Price enrichment disabled for 5sim to avoid timeout issues
 // foreach (\ as &\) {
 // \ = \[\service\];
 // \ = \->get5SimPricesByProduct(\, \, \);
 // if (!empty(\) && isset(\[0][\cost\])) {
 // \[\cost\] = (float)\[0][\cost\];
 // }
 // }
 // unset(\);';

\ = str_replace(\, \, \);

file_put_contents(\, \);
echo \5sim timeout fix applied successfully\\n\;
?>
