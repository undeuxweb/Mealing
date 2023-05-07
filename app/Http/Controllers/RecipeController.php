<?php

namespace App\Http\Controllers;

use Auth;
use Gate;
use App\Models\Recipe;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\TempFile;
use Illuminate\View\View;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;

class RecipeController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('meal_access'), 403);

        return view('recipes.index');
    }

    public function create(): View
    {
        abort_if(Gate::denies('meal_create'), 403);

        $allergens = Allergen::all();
        $categories = Category::all();

        return view('recipes.create', compact('allergens', 'categories'));
    }

    // ここで、StoreRecipeRequestを使っているのは、バリデーションを行うためです。
    // StoreRecipeRequestは、app\Http\Requests\StoreRecipeRequest.phpにあります。
    // StoreRecipeRequestは、FormRequestを継承しています。
    // FormRequestは、Illuminate\Foundation\Http\FormRequestにあります。
    // FormRequestは、Illuminate\Http\Requestを継承しています。
    // Illuminate\Http\Requestは、vendor\laravel\framework\src\Illuminate\Http\Request.phpにあります。
    // Illuminate\Http\Requestは、Symfony\Component\HttpFoundation\Requestを継承しています。
    // Symfony\Component\HttpFoundation\Requestは、vendor\symfony\http-foundation\Request.phpにあります。
    // Symfony\Component\HttpFoundation\Requestは、Symfony\Component\HttpFoundation\RequestStackを継承しています。
    // Symfony\Component\HttpFoundation\RequestStackは、vendor\symfony\http-foundation\RequestStack.phpにあります。
    // Symfony\Component\HttpFoundation\RequestStackは、Symfony\Component\HttpFoundation\RequestMatcherInterfaceを継承しています。
    // Symfony\Component\HttpFoundation\RequestMatcherInterfaceは、vendor\symfony\http-foundation\RequestMatcherInterface.phpにあります。
    // Symfony\Component\HttpFoundation\RequestMatcherInterfaceは、Symfony\Component\HttpFoundation\RequestMatcherを継承しています。
    // Symfony\Component\HttpFoundation\RequestMatcherは、vendor\symfony\http-foundation\RequestMatcher.phpにあります。
    // Symfony\Component\HttpFoundation\RequestMatcherは、Symfony\Component\HttpFoundation\ParameterBagを継承しています。
    // Symfony\Component\HttpFoundation\ParameterBagは、vendor\symfony\http-foundation\ParameterBag.phpにあります。
    // Symfony\Component\HttpFoundation\ParameterBagは、Symfony\Component\HttpFoundation\ParameterBagを継承しています。
    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $recipe = Auth::User()->Recipes()->create([
            'name' => $request['name'],
            'servings' => $request['servings'],
            'adults' => $request->has('adults'),
            'kids' => $request->has('kids'),
            'timing' => $request['timing'],
            'category_id' => $request['category_id'],
            'instruction' => $request['instruction']
        ]);

        $file = TempFile::where('folder', $request->image)->first();
        if ($file) {
            $recipe->addMedia(Storage::path($request->image . '/' . $file->filename))->toMediaCollection();
            $file->delete();
        }

        for ($i = 0; $i < count($request['ingredients']); $i++) {
            $recipe->ingredients()->attach($request['ingredients'][$i], ['quantity' => $request['quantities'][$i]]);
        }

        foreach ($request['allergens'] as $id => $level) {
            if ($level != 'no') {
                $recipe->allergens()->attach($id, ['level' => $level]);
            }
        }

        return redirect($recipe->path());
    }

    public function show(Recipe $recipe): View
    {
        abort_if(Gate::denies('meal_show'), 403);

        $allergens = $recipe->allergens()->pluck('level', 'allergen_id')->toArray();
        $allAllergens = Allergen::all();

        return view('recipes.show', compact('recipe', 'allAllergens', 'allergens'));
    }

    public function edit(Recipe $recipe): View
    {
        abort_if(Gate::denies('meal_edit'), 403);

        // q: load()は何をしているのか？
        // a: リレーションをロードしている。リレーションをロードすることで、リレーションのクエリを発行することなく、リレーションを取得することができる。
        $recipe->load('ingredients', 'allergens');

        $allergens = $recipe->allergens()->pluck('level', 'allergen_id')->toArray();
        $allAllergens = Allergen::all();
        $categories = Category::all();

        return view('recipes.edit', compact('recipe', 'allAllergens', 'allergens', 'categories'));
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $recipe->update($request->validated());

        $file = TempFile::where('folder', $request->image)->first();
        if ($file) {
            $mediaItems = $recipe->getMedia();
            foreach ($mediaItems as $item) {
                $item->delete();
            }

            $recipe->addMedia(Storage::path($request->image . '/' . $file->filename))->toMediaCollection();
            $file->delete();
        }

        foreach ($request['allergens'] as $id => $level) {
            if ($recipe->allergens->contains($id)) {
                if ($level != 'no') {
                    $recipe->allergens()->updateExistingPivot($id, [
                        'level' => $level
                    ]);
                } else {
                    $recipe->allergens()->detach($id);
                }
            } elseif ($level != 'no') {
                $allergen = Allergen::find($id);
                $recipe->allergens()->attach($allergen, ['level' => $level]);
            }
        }

        // syncでこのような配列にする
        // $recipe->ingredients()->sync([
            // 1 => ['quantity' => 100],
            // 2 => ['quantity' => 200],
        // ]);
        $ingredients = array();
        foreach ($request['ingredients'] as $i => $ingredient) {
            $ingredients[$ingredient] = ['quantity' => $request['quantities'][$i]];
        }
        $recipe->ingredients()->sync($ingredients);

        return redirect($recipe->path());
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        abort_if(Gate::denies('meal_delete'), 403);

        $recipe->delete();

        return redirect()->back();
    }

    public function liked(): View
    {
        abort_if(Gate::denies('meal_access'), 403);

        $recipes = Auth()->user()->likedRecipes()->with('media', 'ratings')->paginate(15);

        return view('recipes.liked', compact('recipes'));
    }

    public function like(Recipe $recipe): RedirectResponse
    {
        abort_if(Gate::denies('meal_access'), 403);

        Auth()->user()->likedRecipes()->attach($recipe->id);

        return redirect()->route('recipes.show', $recipe);
    }

    public function unlike(Recipe $recipe): RedirectResponse
    {
        abort_if(Gate::denies('meal_access'), 403);

        DB::table('likes')->where('recipe_id', $recipe->id)->where('user_id', Auth()->id())->delete();

        return redirect()->route('recipes.show', $recipe);
    }
}
