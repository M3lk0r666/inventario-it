<?php

namespace App\Livewire\Admin\Kb;

use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Edición de artículo de KB en página completa (estilo GLPI), con editor
 * enriquecido a todo lo ancho. Crea o edita según se reciba un artículo.
 */
class KbArticleEdit extends Component
{
    use AuthorizesRequests;

    public ?int $articleId = null;

    public ?int $kb_category_id = null;

    public string $title = '';

    public string $body = '';

    public bool $is_published = true;

    public function mount(?KbArticle $article = null): void
    {
        if ($article && $article->exists) {
            $this->authorize('kb.edit');
            $this->articleId = $article->id;
            $this->kb_category_id = $article->kb_category_id;
            $this->title = $article->title;
            $this->body = $article->body;
            $this->is_published = $article->is_published;
        } else {
            $this->authorize('kb.create');
            $this->kb_category_id = request()->integer('category') ?: null;
        }
    }

    public function save()
    {
        $this->authorize($this->articleId ? 'kb.edit' : 'kb.create');

        $data = $this->validate([
            'kb_category_id' => ['required', 'integer', 'exists:kb_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:3'],
            'is_published' => ['boolean'],
        ], [], [
            'kb_category_id' => 'categoría', 'title' => 'título', 'body' => 'contenido',
        ]);

        if ($this->articleId) {
            $article = KbArticle::findOrFail($this->articleId);
            $article->update([
                ...$data,
                'slug' => $this->uniqueSlug($this->title, $article->id),
            ]);
        } else {
            $article = KbArticle::create([
                ...$data,
                'slug' => $this->uniqueSlug($this->title),
                'user_id' => auth()->id(),
            ]);
        }

        session()->flash('toast', 'Artículo guardado.');

        return $this->redirect(route('admin.kb.index', ['read' => $article->id]), navigate: false);
    }

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'articulo';
        $slug = $base;
        $i = 2;
        while (KbArticle::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    #[On('quick-created')]
    public function onQuickCreated(string $catalog, int $id): void
    {
        if ($catalog === 'categorias-kb') {
            $this->kb_category_id = $id;
        }
    }

    public function render()
    {
        return view('livewire.admin.kb.kb-article-edit', [
            'categoryOptions' => KbCategory::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
