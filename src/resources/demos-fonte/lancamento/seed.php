<?php
// Prévia para conferência visual (11/10/2026). Não faz parte do repositório.
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Acesso\Papel;
use App\Models\Configuracao;
use App\Models\User;
use App\Usi\Sincronizador;

$base = rtrim((string) config('app.url'), '/');
$img = fn (string $n) => "{$base}/previa/{$n}.svg";

Configuracao::create(['nome' => 'Imobiliária Exemplo', 'email' => 'contato@exemplo.test', 'whatsapp' => '(11) 99999-0000', 'telefone' => '(11) 2222-3333', 'creci' => '00000-J']);
User::create(['name' => 'Admin', 'email' => 'admin@previa.test', 'password' => 'senha-da-previa-123', 'papel' => Papel::Admin, 'ativo' => true]);

$areas = [
    'capa' => [['url' => $img('capa')]],
    'fotos' => [['url' => $img('fachada-1'), 'titulo' => 'Fachada', 'descricao' => 'Vista da rua principal'], ['url' => $img('fachada-2')], ['url' => $img('fachada-3')]],
    'lazer' => [['url' => $img('piscina'), 'titulo' => 'Piscina'], ['url' => $img('academia')]],
    'decorado' => [['url' => $img('decorado-1')], ['url' => $img('decorado-2')], ['url' => $img('decorado-3')]],
    'implantacao' => [['url' => $img('implantacao')]],
    'plantas' => [['url' => $img('planta-54'), 'titulo' => '54 m² · 2 dormitórios'], ['url' => $img('planta-78')]],
    'obra' => [['url' => $img('obra-1')], ['url' => $img('obra-2')]],
];
$usa = [
    'modern' => ['fotos', 'plantas'], 'classic' => ['capa', 'fotos', 'plantas'], 'minimal' => ['capa', 'plantas', 'implantacao'],
    'gallery' => ['fotos', 'lazer', 'decorado', 'plantas'], 'brochure' => array_keys($areas),
];
$nomes = ['modern' => 'Essencial', 'classic' => 'Capa Cheia', 'minimal' => 'Na Planta', 'gallery' => 'Galerias', 'brochure' => 'Livro'];

$lancamentos = [];
foreach ($usa as $layout => $chaves) {
    $lancamentos[] = [
        'slug' => 'residencial-'.$layout, 'codigo' => 'LAN-'.$layout, 'titulo' => 'Residencial Aurora '.$nomes[$layout],
        'descricao_curta' => 'Dois e três dormitórios a 400 m do metrô, com lazer completo e vista para o parque.',
        'construtora' => 'Construtora Exemplo', 'status_obra' => 'em_obras', 'status_obra_label' => 'Em obras',
        'bairro' => 'Vila Guilherme', 'cidade' => 'São Paulo', 'estado' => 'SP',
        'preco_min' => 420000, 'preco_max' => 690000, 'dorms_resumo' => '2 e 3 dormitórios', 'area_resumo' => '54 a 78 m²',
        'vagas_resumo' => '1 e 2 vagas', 'suites_resumo' => '1 suíte', 'previsao_entrega' => '2027-06', 'destaque' => $layout === 'brochure',
        'logo' => $img('marca'),
        'descricao' => '<p>Um <strong>bairro inteiro</strong> à porta: metrô, parque, escolas e comércio a pé.</p><h3>O projeto</h3><ul><li>Duas torres de 18 andares</li><li>Lazer na cobertura</li></ul><p>Dado inventado para a demonstração.</p>',
        'total_unidades' => 120, 'unidades_disponiveis' => 37, 'total_torres' => 2, 'total_andares' => 18,
        'tipos_unidade' => ['54 m² · 2 dorms', '78 m² · 3 dorms'],
        'area_lazer' => ['Piscina', 'Academia', 'Salão de festas', 'Playground', 'Coworking'],
        'diferenciais' => ['Portaria 24 h', 'Bicicletário', 'Tomada para carro elétrico'],
        'corretor' => 'Beltrana',
        'layout' => $layout, 'areas' => array_intersect_key($areas, array_flip($chaves)),
        'parceiro' => ['nome' => 'Parceira Imóveis', 'creci' => '00000-J'],
        'atualizado_em' => now()->toIso8601String(),
    ];
}

echo app(Sincronizador::class)->aplicar([
    'formato' => 1, 'versao' => now()->getTimestampMs(), 'gerado_em' => now()->toIso8601String(), 'origem' => $base,
    'imobiliaria' => ['nome' => 'Imobiliária Exemplo', 'primary_color' => '#1153a3'],
    'template' => 'alameda', 'imoveis' => [
        ['codigo' => 'PRE-1', 'titulo' => 'Apartamento perto do metrô', 'negocio' => 'Venda', 'negocio_slug' => 'venda', 'categoria' => 'Apartamento', 'categoria_slug' => 'apartamento', 'bairro' => 'Santana', 'cidade' => 'São Paulo', 'estado' => 'SP', 'preco' => 520000, 'dorms' => 2, 'vagas' => 1, 'metragem' => 62, 'descricao' => 'Dois dormitórios, sacada e vaga coberta, a duas quadras do metrô.', 'fotos' => [['url' => $img('fachada-1'), 'thumb' => $img('fachada-1')], ['url' => $img('decorado-1'), 'thumb' => $img('decorado-1')]]],
        ['codigo' => 'PRE-2', 'titulo' => 'Casa térrea com quintal', 'negocio' => 'Venda', 'negocio_slug' => 'venda', 'categoria' => 'Casa', 'categoria_slug' => 'casa', 'bairro' => 'Tucuruvi', 'cidade' => 'São Paulo', 'estado' => 'SP', 'preco' => 780000, 'dorms' => 3, 'descricao' => 'Casa térrea com quintal.', 'fotos' => [['url' => $img('fachada-2'), 'thumb' => $img('fachada-2')]]],
    ], 'lancamentos' => $lancamentos,
]), PHP_EOL;
