<?php

namespace App\Livewire\Admin\Kb;

use App\Mail\KbArticleMail;
use App\Models\Employee;
use App\Models\KbArticle;
use App\Models\KbArticleShare;
use App\Models\KbCategory;
use App\Models\MailingList;
use App\Services\MailConfigurator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Base de conocimientos: navegación por categorías, búsqueda y lectura.
 * La creación/edición vive en su propia página (KbArticleEdit).
 */
class KbManager extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    public ?int $categoryFilter = null;

    #[Url(as: 'read')]
    public ?int $readingId = null;

    public ?int $confirmingDeleteId = null;

    // Compartir por correo
    public bool $sharing = false;

    public ?int $shareArticleId = null;

    public array $shareEmployees = [];   // ids

    public array $shareLists = [];        // ids

    public string $shareEmails = '';      // libres, separados por coma

    public string $shareMessage = '';

    public string $employeeSearch = '';

    public function updatedSearch(): void
    {
        $this->readingId = null;
    }

    public function read(int $id): void
    {
        $article = KbArticle::findOrFail($id);
        $article->increment('views');
        $this->readingId = $id;
    }

    public function backToList(): void
    {
        $this->readingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('kb.delete');
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $this->authorize('kb.delete');
        KbArticle::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->readingId = null;
        $this->dispatch('toast', type: 'success', message: 'Artículo eliminado.');
    }

    // ---- Compartir por correo ----
    public function openShare(int $id): void
    {
        $this->authorize('kb.view');
        $this->reset('shareEmployees', 'shareLists', 'shareEmails', 'shareMessage', 'employeeSearch');
        $this->resetValidation();
        $this->shareArticleId = $id;
        $this->sharing = true;
    }

    public function addEmployee(int $id): void
    {
        if (! in_array($id, $this->shareEmployees)) {
            $this->shareEmployees[] = $id;
        }
        $this->employeeSearch = '';
    }

    public function removeEmployee(int $id): void
    {
        $this->shareEmployees = array_values(array_diff($this->shareEmployees, [$id]));
    }

    public function sendShare(): void
    {
        $this->authorize('kb.view');

        if (! MailConfigurator::isReady()) {
            $this->dispatch('toast', type: 'error', message: 'El correo no está configurado. Ve a Configuración → Correo.');

            return;
        }

        // Reunir destinatarios
        $recipients = collect();
        $recipients = $recipients->merge(
            Employee::whereIn('id', $this->shareEmployees)->pluck('email')->filter()
        );
        $recipients = $recipients->merge(
            MailingList::whereIn('id', $this->shareLists)->pluck('email')
        );
        $recipients = $recipients->merge(
            collect(preg_split('/[,;\s]+/', $this->shareEmails))->map(fn ($e) => trim($e))
        );

        $recipients = $recipients
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()->values();

        if ($recipients->isEmpty()) {
            $this->addError('shareEmails', 'Agrega al menos un destinatario válido (empleado, lista o correo).');

            return;
        }

        MailConfigurator::apply();
        $article = KbArticle::findOrFail($this->shareArticleId);

        Mail::to($recipients->all())->send(
            new KbArticleMail($article, auth()->user()->name, trim($this->shareMessage))
        );

        KbArticleShare::create([
            'kb_article_id' => $article->id,
            'user_id' => auth()->id(),
            'recipients' => $recipients->all(),
            'message' => $this->shareMessage ?: null,
        ]);

        $this->sharing = false;
        $this->dispatch('toast', type: 'success', message: 'Artículo enviado a '.$recipients->count().' destinatario(s).');
    }

    public function render()
    {
        $canEdit = auth()->user()->can('kb.edit');

        $availableEmployees = Employee::where('status', 'active')
            ->whereNotNull('email')
            ->whereNotIn('id', $this->shareEmployees)
            ->when(filled($this->employeeSearch), fn ($q) => $q->where('name', 'like', "%{$this->employeeSearch}%"))
            ->orderBy('name')->limit(8)->get();

        $articles = KbArticle::with(['category', 'author'])
            ->when($this->categoryFilter, fn ($q) => $q->where('kb_category_id', $this->categoryFilter))
            ->when(filled($this->search), fn ($q) => $q->where(fn ($qq) => $qq
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('body', 'like', "%{$this->search}%")))
            ->when(! $canEdit, fn ($q) => $q->where('is_published', true))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.kb.kb-manager', [
            'categories' => KbCategory::withCount('articles')->orderBy('name')->get(),
            'articles' => $articles,
            'reading' => $this->readingId ? KbArticle::with(['category', 'author'])->find($this->readingId) : null,
            'availableEmployees' => $availableEmployees,
            'selectedEmployees' => Employee::whereIn('id', $this->shareEmployees)->orderBy('name')->get(),
            'mailingLists' => MailingList::orderBy('name')->get(),
            'mailReady' => MailConfigurator::isReady(),
        ]);
    }
}
