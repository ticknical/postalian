<?php

namespace App\Tables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class PostalCode
 * PostalCode Table
 *
 * @package App\Tables
 */
class PostalCode extends Model
{
    /**
     * 複数代入しないカラム
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * テーブルのtruncateが必要か返す
     *
     * @return bool
     */
    public function needsTruncate(): bool
    {
        return $this->query()->limit(1)->get()->count() > 0;
    }

    /**
     * 番地が分割されているレコードをマージする
     * 
     * @return bool
     */
    public function mergeSeparatedStreetRecord(): bool
    {
        $merge_codes = [];
        $delele_ids = [];

        /**
         * @var Collection[] $records
         */
        $records = $this
            ->fetchSeparatedStreetRecord()
            ->groupBy('postal_code');

        foreach ($records as $codes)
        {
            $merge_codes[] = array_merge($codes->first()->toArray(), [
                'id' => null,
                'street'      => $codes->implode('street'),
                'street_kana' => $codes->implode('street_kana')
            ]);

            $delele_ids = array_merge($delele_ids, $codes->pluck('id')->all());
        }

        return $this->insert($merge_codes) && $this->destroy($delele_ids);
    }

    /**
     * 「＊＊の次に番地がくる場合」を削除する
     *
     * @return bool
     */
    public function deleteNextAddressStreet(): bool
    {
        $records = $this->fetchNextAddressStreetRecord();

        return $this->query()
            ->whereIn('id', $records->pluck('id')->all())
            ->update([
                'street'      => '',
                'street_kana' => ''
            ]);
    }

    /**
     * 「＊＊一円」を削除する
     *
     * @return bool
     */
    public function deleteThroughoutStreet(): bool
    {
        $records = $this->fetchThroughoutStreet();

        return $this->query()
            ->whereIn('id', $records->pluck('id')->all())
            ->update([
                'street'      => '',
                'street_kana' => ''
            ]);
    }

    /**
     * 番地が分割されているレコードを検索する
     *
     * @return Collection
     */
    protected function fetchSeparatedStreetRecord(): Collection
    {
        return $this->query()
            ->join('postal_codes as tmp', function ($join) {
                $join
                    ->on('postal_codes.postal_code', '=', 'tmp.postal_code')
                    ->on('postal_codes.lpe_code', '=', 'tmp.lpe_code')
                    ->where('tmp.street', 'like', '%（%')
                    ->where('tmp.street', 'not like', '%）');
            })
            ->select('postal_codes.*')
            ->get();
    }

    /**
     * 「＊＊の次に番地がくる場合」を含むレコードを検索する
     *
     * @return Collection
     */
    protected function fetchNextAddressStreetRecord(): Collection
    {
        return $this->query()
            ->where('street', 'like', '%の次に番地がくる場合')
            ->get();
    }

    /**
     * 「＊＊一円」を含むレコードを検索する
     *
     * @return Collection
     */
    protected function fetchThroughoutStreet(): Collection
    {
        return $this->query()
            ->where('street', 'like', '%一円')
            ->where('street', '!=', '一円')
            ->get();
    }
}
