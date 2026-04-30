<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Add KCAT Question</h2></x-slot>

    <div class="mx-auto max-w-5xl py-8">
        <form method="POST" action="{{ route('career-counselor.kcat.questions.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @include('career-counselor.kcat.questions.partials.form', ['question' => null, 'test' => $test])
        </form>
    </div>
</x-app-layout>
