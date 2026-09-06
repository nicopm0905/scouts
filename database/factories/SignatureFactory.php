<?php

namespace Database\Factories;

use App\Enums\SignatureStatus;
use App\Models\Consent;
use App\Models\Document;
use App\Models\Member;
use App\Models\Signature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Signature>
 */
class SignatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'signable_type' => Consent::class,
            'signable_id' => Consent::factory(),
            'member_id' => Member::factory(),
            'public_token' => Str::random(48),
            'status' => SignatureStatus::Pending,
            'sent_at' => null,
            'signed_at' => null,
            'created_by' => null,
        ];
    }

    public function forSignable(Consent|Document $signable): static
    {
        return $this->state(fn () => [
            'signable_type' => $signable->getMorphClass(),
            'signable_id' => $signable->id,
        ]);
    }
}
