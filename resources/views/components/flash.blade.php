@if (session('success'))
    <div class="mb-5 rounded-lg bg-[#EAF3DE] border border-[#C7C1B7]/60 px-4 py-3 text-sm text-[#27500A] flex items-center gap-2.5 shadow-sm">
        <i class="ti ti-circle-check text-lg shrink-0 text-[#27500A]"></i>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mb-5 rounded-lg bg-[#FCEBEB] border border-[#C7C1B7]/60 px-4 py-3 text-sm text-[#791F1F] flex items-center gap-2.5 shadow-sm">
        <i class="ti ti-alert-triangle text-lg shrink-0 text-[#791F1F]"></i>
        <span class="font-medium">{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg bg-[#FCEBEB] border border-[#C7C1B7]/60 px-4 py-3.5 text-sm text-[#791F1F] shadow-sm">
        <div class="flex items-center gap-2 font-semibold mb-1">
            <i class="ti ti-alert-circle text-lg"></i>
            <span>Por favor, verifique os campos abaixo:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 ml-6 text-xs text-[#791F1F]/90">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

