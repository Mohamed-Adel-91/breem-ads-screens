<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * 
 *
 * @property int $id
 * @property string $page
 * @property array|null $title
 * @property array|null $description
 * @property array|null $keywords
 * @property array|null $og_title
 * @property array|null $og_description
 * @property string|null $canonical
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $translations
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta query()
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereCanonical($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereOgDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereOgTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta wherePage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeoMeta whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SeoMeta extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'page',
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'canonical'
    ];

    public $translatable = ['title', 'description', 'keywords', 'og_title', 'og_description'];
}
