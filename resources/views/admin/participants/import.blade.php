@extends('layouts.admin')

@section('title', 'Importar participantes')
@section('section', 'Cadastro')
@section('page-title', 'Importar participantes')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card-surface p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <div class="section-eyebrow text-secondary mb-1">Importação em massa</div>
                    <h2 class="h5 mb-0">Enviar CSV ou Excel</h2>
                </div>
                <a href="{{ route('admin.participants.template') }}" class="btn btn-outline-secondary">Baixar modelo</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Revise o arquivo enviado</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="alert alert-light border mb-4">
                <div class="fw-semibold mb-2">Orientações</div>
                <ul class="mb-0">
                    <li>O arquivo pode ser <strong>.csv</strong>, <strong>.xls</strong> ou <strong>.xlsx</strong>.</li>
                    <li>Para CSV, salve em <strong>UTF-8</strong> para preservar nomes com acento.</li>
                    <li>Use exatamente as colunas do modelo para evitar erros na importação.</li>
                    <li>Status aceitos: <strong>active</strong>, <strong>inactive</strong>, <strong>Ativo</strong> e <strong>Inativo</strong>.</li>
                </ul>
            </div>

            <form action="{{ route('admin.participants.import.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label fw-semibold" for="file">Arquivo da planilha</label>
                    <input type="file" name="file" id="file" class="form-control form-control-lg @error('file') is-invalid @enderror" accept=".csv,.xls,.xlsx">
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-gold btn-lg" type="submit">Importar participantes</button>
                    <a href="{{ route('admin.participants.index') }}" class="btn btn-outline-dark btn-lg">Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card-surface p-4">
            <div class="section-eyebrow text-secondary mb-1">Modelo</div>
            <h3 class="h6 mb-3">Campos esperados</h3>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Nome de exibição</th>
                            <th>Status</th>
                            <th>Edição do retiro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Ana Oliveira</td>
                            <td>Ana Oliveira</td>
                            <td>active</td>
                            <td>Vida Vitoriosa 2026</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
