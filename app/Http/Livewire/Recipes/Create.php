<?php

namespace App\Http\Livewire\Recipes;

use App\Models\Recipe;
use Livewire\Component;
use App\Models\Ingredient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Create extends Component
{
    public Recipe $recipe;
    public $quantity;
    public $query;
    public $ingredients;
    public $ingredientId;
    public $autocomplete;
    public $ids;
    public $inputs;
    public $i;

    protected $rules = [
        'quantity' => 'required',
        'query' => 'required',
        'ingredientId' => 'required|numeric'
    ];

    /**
     * setting up variable deafults
     *
     * @return void
     */
    public function mount()
    {
        $this->resetQuery();
        $this->inputs = array();
        $this->i = 0;
        $this->ids = array();

        if (isset($this->recipe)) {
            foreach ($this->recipe->ingredients as $i => $ingredient) {
                $this->inputs[$i]['quantity'] = $ingredient->pivot->quantity;
                $this->inputs[$i]['ingredient'] = $ingredient->name;
                $this->inputs[$i]['ingredientId'] = $ingredient->id;
            }
        }
    }

    /**
     * reseting query variables
     *
     * @return void
     */
    public function resetQuery()
    {
        $this->query = '';
        $this->quantity = '';
        $this->ingredients = array();
        $this->ingredientId = '';
        $this->autocomplete = false;
    }

    /**
     * getting the view
     *
     * @return view
     */
    public function render()
    {
        return view('livewire.recipes.create');
    }

    /**
     * adding all input values to array
     *
     * @return array
     */
    public function add($i)
    {
        $this->validate();

        $this->inputs[$i]['quantity'] = $this->quantity;
        $this->inputs[$i]['ingredient'] = $this->query;
        $this->inputs[$i]['ingredientId'] = $this->ingredientId;

        array_push($this->ids, $this->ingredientId);

        $this->i = $i + 1;

        $this->resetQuery();
    }

    /**
     * removing inputs from the array
     *
     * @return void
     */
    public function remove($i)
    {
        if (($key = array_search($this->inputs[$i]['ingredientId'], $this->ids)) !== false) {
            unset($this->ids[$key]);
        }

        unset($this->inputs[$i]);
    }

    /**
     * get all the ingredients from the query
     *
     * @return void
     */
    public function updatedQuery()
    {
        if ($this->query != '') {
            $this->autocomplete = false;
            if (config("database.default") == "mysql") {
                /**
                 * LOCATE は、SQLの文字列関数の1つで、ある文字列が別の文字列の中で最初に現れる位置を返す関数です。
                 * MySQLやMariaDBなどのデータベース管理システムで使用されます。
                 * LOCATE関数は2つの引数を受け取ります。
                 * 最初の引数は、検索対象の文字列（部分文字列）です。
                 * 二番目の引数は、検索対象文字列が含まれるかどうかを調べる元の文字列です。
                 * 例えば、以下のSQL文は、name列の中で$this->queryという文字列が最初に現れる位置を取得し、その結果に基づいて並び替えを行います。
                 */
                $this->ingredients = Ingredient::where('name', 'like', '%' . $this->query . '%')
                    ->orderByRaw('LOCATE(\'' . $this->query . '\', name)')
                    ->take(4)
                    ->get()
                    ->toArray();
            }
            if (config("database.default") == "sqlite") {
                $this->ingredients = Ingredient::where('name', 'like', '%' . $this->query . '%')
                    ->orderByRaw('INSTR(\'' . $this->query . '\', name)')
                    ->take(4)
                    ->get()
                    ->toArray();
            }
        } else {
            $this->ingredients = array();
        }
    }

    /**
     * change query and ingredientId value on selection
     *
     * @return void
     */
    public function autocomplete($query, $id)
    {
        $this->query = $query;
        $this->ingredientId = $id;
        $this->autocomplete = true;
    }

    /**
     * create an ingredient if it doesn't exist
     *
     * @return void
     */
    public function createIngredient()
    {
        /**
         * unique:App\Models\Ingredient,name：
         * このルールは、queryの値がApp\Models\Ingredientモデルのname属性として一意であることを要求しています。
         * つまり、queryの値が既にデータベースに存在するIngredientモデルのname属性と同じであってはならないという制約があります。
         */
        $this->validate([
            'query' => 'required|unique:App\Models\Ingredient,name',
        ]);

        /**
         * Auth::User(): 現在認証されているユーザーを取得します。
         * このユーザーオブジェクトは、リレーショナルデータベース（おそらくEloquent ORMを使用）に関連付けられたメソッドやプロパティを持っています。
         * ->Ingredients(): これは、現在認証されているユーザーに関連するIngredientオブジェクトのコレクションを取得するメソッドです。
         * Eloquentのリレーションシップ（おそらくhasManyリレーションシップ）が設定されていることを示しています。
         * ->create(): このメソッドは、新しいIngredientオブジェクトをデータベースに保存するために使われます。
         * このメソッドの引数として、連想配列が渡されており、その中に'name' => $this->queryというキーと値のペアが含まれています。
         * これは、新しいIngredientオブジェクトのname属性に$this->queryの値を設定することを意味しています。
         */
        $ingredient = Auth::User()->Ingredients()->create([
            'name' => $this->query
        ]);

        $this->autocomplete($this->query, $ingredient->id);
    }
}
