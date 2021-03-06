<?php

namespace GetCandy\Api\Products\Models;

use GetCandy\Api\Attributes\Models\Attribute;
use GetCandy\Api\Scaffold\BaseModel;
use GetCandy\Api\Traits\HasAttributes;
use GetCandy\Api\Assets\Models\Asset;
use GetCandy\Api\Taxes\Models\Tax;
use PriceCalculator;
use Facades\GetCandy\Api\Taxes\TaxCalculator;

class ProductVariant extends BaseModel
{
    use HasAttributes;

    protected $pricing;

    /**
     * The Hashid Channel for encoding the id
     * @var string
     */
    protected $hashids = 'product';

    protected $fillable = ['options', 'price', 'sku', 'stock'];


    public function product()
    {
        return $this->belongsTo(Product::class)->withoutGlobalScopes();
    }

    public function getNameAttribute()
    {
        //TODO: Figure out a more dynamic way to do this
        $name = '';
        $localeUsed = 'en';
        $locale = app()->getLocale();
        $i = 0;

        foreach ($this->options as $handle => $option) {
            if (!empty($option[$locale])) {
                $localeUsed = $locale;
            }
            $name .= $option[$localeUsed] . ($i == count($this->options) ? ', ' : '');
        }

        return $name;
    }

    public function getOptionsAttribute($val)
    {
        $values = [];
        $option_data = $this->product->option_data;

        foreach (json_decode($val, true) as $option => $value) {
            if (! empty($data = $option_data[$option])) {
                $values[$option] = $data['options'][$value]['values'];
            }
        }
        return $values;
    }

    protected function getPricing($type)
    {
        if (!$this->pricing) {
            $this->pricing = app('api')->productVariants()->getVariantPrice($this, app('auth')->user());
        }
        return $this->pricing;
    }

    public function getTotalPriceAttribute()
    {
        return $this->getPricing('price')->amount;
    }

    public function getTaxTotalAttribute()
    {
        return $this->getPricing('tax')->tax;
    }

    public function setOptionsAttribute($val)
    {
        $options = [];
        foreach ($val as $option => $value) {
            if (is_array($value)) {
                $value = reset($value);
            }
            $options[str_slug($option)] = str_slug($value);
        }
        $this->attributes['options'] = json_encode($options);
    }

    public function image()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function customerPricing()
    {
        return $this->hasMany(ProductCustomerPrice::class);
    }

    public function tiers()
    {
        return $this->hasMany(ProductPricingTier::class);
    }
}
