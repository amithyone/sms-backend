<?php
\ = 'app/Services/SmsProviderService.php';
\ = file_get_contents(\);

// Find and replace the problematic foreach loop
\ = 'foreach (\ as &\) {
                        \ = \[" service\];
 \ = \->get5SimPricesByProduct(\, \, \);
 if (!empty(\) && isset(\[0][\cost\])) {
 \[\cost\] = (float)\[0][\cost\];
 }
 }
 unset(\);';

\ = '// Price enrichment disabled for 5sim to avoid timeout
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
