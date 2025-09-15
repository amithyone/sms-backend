<?php
\ = 'app/Http/Controllers/WalletController.php';
\ = file_get_contents(\);

// Add charges calculation method
\ = '    private function calculateCharges(\)
    {
        if (\ >= 1000 && \ <= 10000) {
            return (\ * 0.015) + 100;
        } elseif (\ > 10000 && \ <= 20000) {
            return (\ * 0.015) + 200;
        } elseif (\ > 20000 && \ <= 40000) {
            return (\ * 0.015) + 300;
        } else {
            return (\ * 0.02) + 200;
        }
    }';

// Add charges calculation after amount validation
\ = '        // Calculate charges based on tiered structure
        \ = \->calculateCharges(\);
        \ = \ + \;';

// Apply changes
\ = str_replace('        \ = (int) \[" amount\];', \ . \\\n\ . ' \ = (int) \[\amount\];', \);
\ = str_replace(' \amount\ => \,', ' \amount\ => \,' . \\\n\ . ' \charges\ => \,', \);
\ = str_replace(' }', \ . \\\n\ . ' }', \);

file_put_contents(\, \);
echo \Charges calculation added successfully\\n\;
?>
