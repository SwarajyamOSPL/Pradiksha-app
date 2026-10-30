<?php

namespace App\Concerns;

use App\Models\Package;

/**
 * The admin package form fields for what comes with a package besides its products:
 * the worth of the goods and the extras list, typed one item per line.
 */
trait ManagesPackageExtras
{
    public ?string $goods_worth = null;

    public string $inclusionsText = '';

    protected function fillPackageExtras(Package $package): void
    {
        $this->goods_worth = $package->goods_worth !== null ? (string) ((float) $package->goods_worth) : null;
        $this->inclusionsText = implode("\n", $package->inclusionList());
    }

    /**
     * Package attributes for the extras, ready to store.
     *
     * @return array{goods_worth: float|null, inclusions: array<int, string>}
     */
    protected function extrasAttributes(): array
    {
        $lines = preg_split('/\R/', $this->inclusionsText) ?: [];

        return [
            'goods_worth' => filled($this->goods_worth) && is_numeric($this->goods_worth) ? round((float) $this->goods_worth, 2) : null,
            'inclusions' => array_values(array_filter(array_map(trim(...), $lines), 'filled')),
        ];
    }
}
