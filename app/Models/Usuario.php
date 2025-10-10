use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    protected $fillable = [
	'nome',
    'cpf',
    'telefone',
    'endereco',
	'email',
	'senha',
	]
}