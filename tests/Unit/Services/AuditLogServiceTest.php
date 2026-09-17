<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuditLogService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditLogServiceTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
        ]);

        Auth::login($this->user);
    }

    /** @test */
    public function log_cria_registro_rn14(): void
    {
        $auditLog = AuditLogService::log(
            'teste_acao',
            'teste_entidade',
            123,
            ['chave' => 'valor', 'outra' => 'dado']
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('teste_acao', $auditLog->acao);
        $this->assertEquals('teste_entidade', $auditLog->entidade);
        $this->assertEquals(123, $auditLog->entidade_id);
        $this->assertEquals($this->user->id, $auditLog->usuario_id);
        $this->assertEquals(['chave' => 'valor', 'outra' => 'dado'], $auditLog->detalhes);
        $this->assertNotNull($auditLog->ip_address);
    }

    /** @test */
    public function log_sem_entidade_id_funciona(): void
    {
        $auditLog = AuditLogService::log('acao_sem_id', 'entidade_sem_id');

        $this->assertNull($auditLog->entidade_id);
        $this->assertEquals('acao_sem_id', $auditLog->acao);
    }

    /** @test */
    public function log_sem_detalhes_funciona(): void
    {
        $auditLog = AuditLogService::log('acao_sem_detalhes', 'entidade', 456, null);

        $this->assertNull($auditLog->detalhes);
    }

    /** @test */
    public function list_filtra_por_acao(): void
    {
        AuditLog::factory()->count(3)->create(['acao' => 'acao_teste', 'usuario_id' => $this->user->id]);
        AuditLog::factory()->count(2)->create(['acao' => 'outra_acao', 'usuario_id' => $this->user->id]);

        $result = AuditLogService::list(['acao' => 'acao_teste']);

        $this->assertEquals(3, $result->total());
        foreach ($result->items() as $item) {
            $this->assertEquals('acao_teste', $item->acao);
        }
    }

    /** @test */
    public function list_filtra_por_entidade(): void
    {
        AuditLog::factory()->count(2)->create(['entidade' => 'users', 'usuario_id' => $this->user->id]);
        AuditLog::factory()->count(3)->create(['entidade' => 'agendamentos', 'usuario_id' => $this->user->id]);

        $result = AuditLogService::list(['entidade' => 'users']);

        $this->assertEquals(2, $result->total());
        foreach ($result->items() as $item) {
            $this->assertEquals('users', $item->entidade);
        }
    }

    /** @test */
    public function list_filtra_por_usuario(): void
    {
        $outroUser = User::factory()->create();
        AuditLog::factory()->count(2)->create(['usuario_id' => $this->user->id]);
        AuditLog::factory()->count(3)->create(['usuario_id' => $outroUser->id]);

        $result = AuditLogService::list(['usuario_id' => $this->user->id]);

        $this->assertEquals(2, $result->total());
        foreach ($result->items() as $item) {
            $this->assertEquals($this->user->id, $item->usuario_id);
        }
    }

    /** @test */
    public function list_filtra_por_data(): void
    {
        AuditLog::factory()->create(['created_at' => now()->subDays(10), 'usuario_id' => $this->user->id]);
        AuditLog::factory()->create(['created_at' => now()->subDays(5), 'usuario_id' => $this->user->id]);
        AuditLog::factory()->create(['created_at' => now(), 'usuario_id' => $this->user->id]);

        $result = AuditLogService::list([
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $this->assertEquals(2, $result->total()); // últimos 7 dias
    }

    /** @test */
    public function list_ordena_por_data_decrescente(): void
    {
        $antigo = AuditLog::factory()->create(['created_at' => now()->subDays(5), 'usuario_id' => $this->user->id]);
        $novo = AuditLog::factory()->create(['created_at' => now(), 'usuario_id' => $this->user->id]);

        $result = AuditLogService::list([]);

        $this->assertEquals($novo->id, $result->first()->id);
        $this->assertEquals($antigo->id, $result->last()->id);
    }

    /** @test */
    public function list_paginacao_padrao_50(): void
    {
        AuditLog::factory()->count(60)->create(['usuario_id' => $this->user->id]);

        $result = AuditLogService::list([]);

        $this->assertEquals(50, $result->perPage());
        $this->assertEquals(60, $result->total());
    }

    /** @test */
    public function list_paginacao_customizada(): void
    {
        AuditLog::factory()->count(25)->create(['usuario_id' => $this->user->id]);

        $result = AuditLogService::list(['per_page' => 10]);

        $this->assertEquals(10, $result->perPage());
    }
}