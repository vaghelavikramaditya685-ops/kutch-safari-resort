<?php
/* The offline path. Saved here AND emailed, so nothing is lost if mail fails. */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/mail.php';
require_post();
rate_limit('enquiry', 8, 600);

$name  = in_str('name');
$phone = in_str('phone');
if (!$name || !$phone) json_fail('Please give your name and phone number.');

// Honeypot: a real guest never fills a hidden field.
if (in_str('website') !== '') json_out(['ok' => true]);

// Everything given must make sense (the same rules as a booking).
$email = in_str('email'); $in = in_str('check_in'); $out = in_str('check_out'); $guests = in_str('guests');
if (!valid_phone($phone))                                        json_fail('That phone number does not look right. Use digits only, for example 98250 12345.');
if (too_long($name, 'name'))                                     json_fail('Please keep the name under ' . LIMITS['name'] . ' characters.');
if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || too_long($email, 'email'))) json_fail('That email address does not look right.');
if (($in !== '' && !valid_date($in)) || ($out !== '' && !valid_date($out))) json_fail('Please choose real dates (or leave them blank).');
if ($in !== '' && $out !== '' && $out <= $in)                    json_fail('The departure date must be after the arrival date.');
if ($guests !== '' && (!ctype_digit($guests) || (int) $guests < 1 || (int) $guests > 500)) json_fail('Please give the number of guests as a number (1 or more).');
if (too_long(in_str('interest'), 'interest'))                    json_fail('Please keep that shorter.');
if (too_long(in_str('message'), 'enquiry_message'))              json_fail('Please keep your message under ' . number_format(LIMITS['enquiry_message']) . ' characters.');

$property = q1("SELECT id FROM properties WHERE code = ?", [in_str('property')]);

$data = [
    'property_id' => $property['id'] ?? null,
    'name'      => $name,
    'phone'     => $phone,
    'email'     => in_str('email') ?: null,
    'check_in'  => in_str('check_in') ?: null,
    'check_out' => in_str('check_out') ?: null,
    'guests'    => in_str('guests') ?: null,
    'interest'  => in_str('interest') ?: null,
    'message'   => in_str('message') ?: null,
    'status'    => 'new',
    'created_at'=> now(),
];
$id = insert('enquiries', $data);
audit('enquiry_received', 'enquiry', $id, ['name' => $name]);
send_enquiry_notification($data);

json_out(['ok' => true, 'id' => $id,
          'message' => 'Thank you — we have your enquiry and will reply today.']);
