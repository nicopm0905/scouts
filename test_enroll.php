<?php

use App\Models\EventMember;

$member = EventMember::find(170);
echo json_encode($member->toArray(), JSON_PRETTY_PRINT)."\n";
