<?php

namespace App\Models;

use App\Models\Pivot\CustomerObjectiveKcalMealType;
use App\Support\Traits\DocumentTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


/**
 * App\Models\Customer
 *
 * @property string $id
 * @property string $user_id
 * @property int|null $goal_id
 * @property int|null $activity_level_id
 * @property int|null $profile_image_id
 * @property string|null $gender
 * @property string $unique_key
 * @property string|null $username
 * @property string|null $profile_password
 * @property string|null $activity
 * @property string|null $height
 * @property string|null $type_water
 * @property int|null $obj_kcals
 * @property int|null $train_kcal
 * @property int|null $rest_kcal
 * @property int|null $obj_steps
 * @property int|null $obj_weight_hebdo_change
 * @property int|null $max_weight_hebdo_change
 * @property int|null $time_to_achieve
 * @property float|null $start_weight
 * @property float|null $wanted_weight
 * @property float|null $obj_water
 * @property float|null $volume_water
 * @property float|null $volume_water_by_each
 * @property float|null $obj_proteins
 * @property float|null $obj_lipids
 * @property float|null $obj_glucides
 * @property bool $is_owner
 * @property bool $profile_locked
 * @property bool $accept_marketing
 * @property bool $filled_first_form
 * @property Carbon|null $birth_date
 * @property Carbon|null $form_passed_at
 * @property Carbon|null $last_login_at
 * @property Carbon|null $last_paywall_at
 * @property Carbon|null $rated_app_at
 * @property string|null $picture_from_provider
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property array|null $training_days_of_week
 *
 * @property-read User $user
 * @property-read Goal|null $goal
 * @property-read ActivityLevel|null $activityLevel
 * @property-read Document|null $customerProfilePicture
 * @property-read Collection|BodyMass[] $customerWeights
 * @property-read Collection|Water[] $waterCustomers
 */
class Customer extends Model implements Documentable
{
    use HasFactory, HasUuids, Notifiable, DocumentTrait;

    protected $fillable = [
        'gender',
        'unique_key',
        'username',
        'profile_password',
        'activity_level_id',
        'goal_id',
        'height',
        'type_water',
        'obj_kcals',
        'train_kcal',
        'rest_kcal',
        'obj_steps',
        'obj_weight_hebdo_change',
        'max_weight_hebdo_change',
        'time_to_achieve',
        'start_weight',
        'wanted_weight',
        'obj_water',
        'volume_water',
        'volume_water_by_each',
        'obj_proteins',
        'obj_lipids',
        'obj_glucides',
        'user_id',
        'is_owner',
        'profile_locked',
        'accept_marketing',
        'filled_first_form',
        'birth_date',
        'form_passed_at',
        'last_login_at',
        'last_paywall_at',
        'rated_app_at',
        'picture_from_provider',
        'training_days_of_week',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_key)) {
                $model->unique_key = Str::uuid()->toString(); // Génère un UUID unique
            }
        });
    }

    protected $table = 'customers';
    protected $casts = [
        'profile_locked' => 'boolean',
        'is_owner' => 'boolean',
        'accept_marketing' => 'boolean',
        'filled_first_form' => 'boolean',
        'last_login_at' => 'datetime',
        'last_paywall_at' => 'datetime',
        'rated_app_at' => 'datetime',
        'birth_date' => 'date',
        'form_passed_at' => 'date',
        'training_days_of_week' => 'array',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function goal(): BelongsTo {
        return $this->belongsTo(Goal::class);
    }

    public function activityLevel(): BelongsTo {
        return $this->belongsTo(ActivityLevel::class);
    }
    public function customerWeights(): HasMany {
        return $this->hasMany(BodyMass::class);
    }

    public function waterCustomers(): HasMany {
        return $this->hasMany(Water::class);
    }

    public function storagePath(): string
    {
        return 'customers/' . $this->id . '/profile_image';
    }

    public function customerProfilePicture(): BelongsTo {
        return $this->belongsTo(Document::class, 'profile_image_id');
    }

    public function bodyMasses(): HasMany
    {
        return $this->hasMany(BodyMass::class, 'customer_id', 'id');
    }

    public function objectiveKcalMealTypes(): HasMany
    {
        return $this->hasMany(ObjectiveKcalMealType::class, 'customer_id')
            ->with('mealType');
    }

    public function getMealTypeAsArray (): array
    {
        return $this->objectiveKcalMealTypes
            ->map(fn ($type) => $type->mealType->value)
            ->toArray();
    }

}
