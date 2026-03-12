<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Fee Structure
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <form method="POST" action="{{ route('principal.fees.structures.update', $feeStructure) }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" required class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected(old('session', $feeStructure->session) === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" name="class_id" required class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) old('class_id', $feeStructure->class_id) === (string) $classRoom->id)>
                                        {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="title" value="Title" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block min-h-11 w-full" value="{{ old('title', $feeStructure->title) }}" required />
                        </div>

                        <div>
                            <x-input-label for="amount" value="Amount" />
                            <x-text-input id="amount" name="amount" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('amount', $feeStructure->amount) }}" required />
                        </div>

                        <div>
                            <x-input-label for="fee_type" value="Fee Type" />
                            <select id="fee_type" name="fee_type" required class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($feeTypes as $feeType)
                                    <option value="{{ $feeType }}" @selected(old('fee_type', $feeStructure->fee_type) === $feeType)>{{ ucwords(str_replace('_', ' ', $feeType)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="inline-flex min-h-11 items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_monthly" value="1" @checked(old('is_monthly', $feeStructure->is_monthly)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Monthly recurring fee
                            </label>
                            <label class="inline-flex min-h-11 items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $feeStructure->is_active)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Active
                            </label>
                        </div>

                        <div class="md:col-span-2 flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Update Structure
                            </button>
                            <a href="{{ route('principal.fees.structures.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
