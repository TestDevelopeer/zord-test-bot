<div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column">
    <div class="row">
        <div class="col-12">
            <h4 class="fw-bolder">
                <x-orchid-icon path="bs.broadcast" class="me-2" />
                Статистика пользователей
            </h4>
            <p class="text-muted small">
                Информация о пользователях для массовой рассылки
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success">
                        <x-orchid-icon path="bs.people" class="h1" />
                    </div>
                    <h3 class="fw-bolder text-success">{{ $stats['active_users'] ?? 0 }}</h3>
                    <p class="text-muted small mb-0">Активных пользователей</p>
                    <small class="text-muted">Получат сообщение</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary">
                        <x-orchid-icon path="bs.telegram" class="h1" />
                    </div>
                    <h3 class="fw-bolder text-primary">{{ $stats['total_with_telegram'] ?? 0 }}</h3>
                    <p class="text-muted small mb-0">Всего с Telegram</p>
                    <small class="text-muted">Пользователей в базе</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-danger">
                        <x-orchid-icon path="bs.x-circle" class="h1" />
                    </div>
                    <h3 class="fw-bolder text-danger">{{ $stats['kicked_users'] ?? 0 }}</h3>
                    <p class="text-muted small mb-0">Заблокировали бота</p>
                    <small class="text-muted">Будут пропущены</small>
                </div>
            </div>
        </div>
    </div>

    @if (($stats['active_users'] ?? 0) === 0)
        <div class="alert alert-warning mt-3">
            <x-orchid-icon path="bs.exclamation-triangle" class="me-2" />
            <strong>Предупреждение:</strong> Нет активных пользователей для рассылки.
        </div>
    @endif
</div>
