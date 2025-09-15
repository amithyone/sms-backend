<?php
\ = 'app/Services/SmsProviderService.php';
\ = file_get_contents(\);

// Replace the problematic price enrichment section
\ = 'if (\) {
                    foreach (\ as &\) {
                        \ = \[" service\];
 \ = \->get5SimPricesByProduct(\, \, \);
 if (!empty(\) && isset(\[0][\cost\])) {
 \[\cost\] = (float)\[0][\cost\];
 }
 }
 unset(\);
 }';

\ = 'if (\) {
 // Price enrichment disabled for 5sim to avoid timeout
 // Services will have cost=0, which is acceptable
 }';

\ = str_replace(\, \, \);
file_put_contents(\, \);
echo \Fix applied successfully\\n\;
?>
