<?php
echo "env('MAIL_FROM_ADDRESS'): " . var_export(env('MAIL_FROM_ADDRESS'), true) . "\n";
echo "env('MAIL_SAVE_TO_SENT_ITEMS'): " . var_export(env('MAIL_SAVE_TO_SENT_ITEMS'), true) . "\n";
echo "config('mail.from.address'): " . var_export(config('mail.from.address'), true) . "\n";
echo "config('mail.save_to_sent_items'): " . var_export(config('mail.save_to_sent_items'), true) . "\n";
