<?php

namespace App\Models;

use App\Http\Resources\DomainResource;
use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Domain extends Model
{
    use HasUuid, HasEncryptedAttributes, HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $encrypted = [
        'description'
    ];

    protected $fillable = [
        'domain',
        'description',
        'active',
        'catch_all'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'domain_verified_at',
        'domain_mx_validated_at',
        'domain_sending_verified_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'active' => 'boolean',
        'catch_all' => 'boolean',
        'default_recipient_id' => 'string',
    ];

    public static function boot()
    {
        parent::boot();

        Domain::deleting(function ($domain) {
            $domain->aliases()->withTrashed()->forceDelete();
        });
    }

    /**
     * Set the domain's name.
     */
    public function setDomainAttribute($value)
    {
        $this->attributes['domain'] = strtolower($value);
    }

    /**
     * Get the user for the custom domain.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the domains's aliases.
     */
    public function aliases()
    {
        return $this->morphMany(Alias::class, 'aliasable');
    }

    /**
     * Get the domains's default recipient.
     */
    public function defaultRecipient()
    {
        return $this->hasOne(Recipient::class, 'id', 'default_recipient_id');
    }

    /**
     * Set the domains's default recipient.
     */
    public function setDefaultRecipientAttribute($recipient)
    {
        $this->attributes['default_recipient_id'] = $recipient->id;
        $this->setRelation('defaultRecipient', $recipient);
    }

    /**
     * Deactivate the domain.
     */
    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    /**
     * Activate the domain.
     */
    public function activate()
    {
        $this->update(['active' => true]);
    }

    /**
     * Disable catch-all for the domain.
     */
    public function disableCatchAll()
    {
        $this->update(['catch_all' => false]);
    }

    /**
     * Enable catch-all for the domain.
     */
    public function enableCatchAll()
    {
        $this->update(['catch_all' => true]);
    }

    /**
     * Determine if the domain is verified.
     *
     * @return bool
     */
    public function isVerified()
    {
        return ! is_null($this->domain_verified_at);
    }

    /**
     * Determine if the domain is verified for sending.
     *
     * @return bool
     */
    public function isVerifiedForSending()
    {
        return ! is_null($this->domain_sending_verified_at);
    }

    /**
     * Mark this domain as verified.
     *
     * @return bool
     */
    public function markDomainAsVerified()
    {
        return $this->forceFill([
            'domain_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Mark this domain as verified for sending.
     *
     * @return bool
     */
    public function markDomainAsVerifiedForSending()
    {
        return $this->forceFill([
            'domain_sending_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Mark this domain as having valid MX records.
     *
     * @return bool
     */
    public function markDomainAsValidMx()
    {
        return $this->forceFill([
            'domain_mx_validated_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Checks if the domain has the correct records.
     */
    public function checkVerification()
    {
        if (App::environment('testing')) {
            return true;
        }

        return collect(dns_get_record($this->domain . '.', DNS_TXT))
            ->contains(function ($r) {
                return trim($r['txt']) === 'aa-verify=' . sha1(config('anonaddy.secret') . user()->id . user()->domains->count());
            });
    }

    /**
     * Checks if the domain has the correct MX records.
     */
    public function checkMxRecords()
    {
        if (App::environment('testing')) {
            return true;
        }

        $mx = collect(dns_get_record($this->domain . '.', DNS_MX))
            ->sortBy('pri')
            ->first();

        if (! isset($mx['target'])) {
            return false;
        }

        if ($mx['target'] !== config('anonaddy.hostname')) {
            return false;
        }

        $this->markDomainAsValidMx();

        return true;
    }

    /**
     * Checks if the domain has the correct records for sending.
     */
    public function checkVerificationForSending()
    {
        if (App::environment('testing')) {
            return response()->json([
                'success' => true,
                'message' => 'Records verified for sending.',
            ]);
        }

        $spf = collect(dns_get_record($this->domain . '.', DNS_TXT))
            ->contains(function ($r) {
                return preg_match("/^(v=spf1).*(include:spf\." . config('anonaddy.domain') . "|mx).*(-|~)all$/", $r['txt']);
            });

        if (!$spf) {
            return response()->json([
                'success' => false,
                'message' => 'SPF record not found. This could be due to DNS caching, please try again later.'
            ]);
        }

        $dmarc = collect(dns_get_record('_dmarc.' . $this->domain . '.', DNS_TXT))
            ->contains(function ($r) {
                return preg_match("/^(v=DMARC1).*(p=quarantine|reject).*/", $r['txt']);
            });

        if (!$dmarc) {
            return response()->json([
                'success' => false,
                'message' => 'DMARC record not found. This could be due to DNS caching, please try again later.'
            ]);
        }

        $def = collect(dns_get_record('default._domainkey.' . $this->domain . '.', DNS_CNAME))
            ->contains(function ($r) {
                return $r['target'] === 'default._domainkey.' . config('anonaddy.domain');
            });

        if (!$def) {
            return response()->json([
                'success' => false,
                'message' => 'CNAME default._domainkey record not found. This could be due to DNS caching, please try again later.'
            ]);
        }

        $this->markDomainAsVerifiedForSending();

        return response()->json([
            'success' => true,
            'message' => 'Records successfully verified.',
            'data' => new DomainResource($this->fresh())
        ]);
    }
}
