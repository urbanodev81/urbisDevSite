<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Configuracao;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * O catálogo da configuração do site (bloco 4a da pendência 246, 06/11/2026).
 * Copiado do Duo (`parceiros/duo-pati-e-guita`) e enxugado.
 *
 * **Uma chave que não está em `CATALOGO` não existe.** Não é gravada, não é
 * lida e não aparece na tela (regra de ouro do PADROES.md §Site público). A
 * validação sai daqui (`regras()`), a tela se desenha daqui (`paraTela()`) e o
 * site lê daqui (`valor()`, ou `$site('chave')` nas views).
 *
 * **O padrão de cada chave é o texto que estava no Blade até 06/11.** É ele que
 * aparece com a tabela vazia e, mais importante, com o Postgres FORA: o site
 * público não pode cair porque o banco caiu (`SitePublicoSemBancoTest`). Por
 * isso `todas()` engole a exceção e devolve os padrões, sem guardar no cache
 * (senão o site ficaria com os padrões até alguém salvar de novo).
 *
 * Antes deste arquivo, o e-mail estava escrito em 7 lugares, o telefone em 4 e
 * a descrição da empresa em 2, com redações diferentes.
 */
final class ConfiguracaoDoSite
{
    private const CHAVE_CACHE = 'configuracoes.site';

    /** Pasta, no disco `public`, das imagens que o painel sobe para o site. */
    public const PASTA_IMAGENS = 'site';

    /** Medida da imagem de compartilhamento (Open Graph). */
    public const OG = [1200, 630];

    /** @var array<string, string|null>|null memo da requisição */
    private static ?array $memo = null;

    /**
     * `tipo`: texto · texto_longo · email · url · whatsapp · imagem.
     * `regras` substitui a validação do tipo. O `max` dos textos da home é
     * medida de LAYOUT (quebra de linha no celular), não de banco.
     * `enfase`: o texto aceita *asteriscos* em volta do trecho destacado.
     *
     * @var array<string, array{grupo: string, tipo: string, rotulo: string, ajuda?: string, padrao?: string, regras?: list<string>, enfase?: bool}>
     */
    public const CATALOGO = [
        // ---------------------------------------------------------- contato
        'contato.email' => [
            'grupo' => 'contato', 'tipo' => 'email', 'rotulo' => 'E-mail de contato',
            'ajuda' => 'Aparece na home, no botão de e-mail, na política de privacidade e para buscadores e IAs. '
                .'Não muda para onde vão os avisos de contato (isso é do servidor).',
            'padrao' => 'contato@urbisdev.tech',
            'regras' => ['required', 'email', 'max:255'],
        ],
        'contato.whatsapp' => [
            'grupo' => 'contato', 'tipo' => 'whatsapp', 'rotulo' => 'WhatsApp',
            'ajuda' => 'Só números, com o 55 e o DDD. Ex.: 5511976862551. O telefone escrito nas páginas sai deste número.',
            'padrao' => '5511976862551',
            'regras' => ['required', 'regex:/^55\d{10,11}$/'],
        ],
        'contato.mensagem_whatsapp' => [
            'grupo' => 'contato', 'tipo' => 'texto', 'rotulo' => 'Mensagem pronta do WhatsApp',
            'ajuda' => 'O texto que já vem escrito quando a pessoa toca no botão do WhatsApp. '
                .'No blog, o botão manda uma mensagem própria, com o nome do texto.',
            'padrao' => 'Olá! Vim pelo site da UrbisDev e gostaria de saber mais.',
            'regras' => ['required', 'string', 'max:200'],
        ],

        // ------------------------------------------------------------ redes
        'redes.instagram' => [
            'grupo' => 'redes', 'tipo' => 'url', 'rotulo' => 'Instagram',
            'ajuda' => 'Endereço completo do perfil. Vazio, o ícone não aparece (de 31/07 a 18/10/2026 ele apontava para lugar nenhum).',
            'regras' => ['nullable', 'url:https', 'max:300'],
        ],
        'redes.linkedin' => [
            'grupo' => 'redes', 'tipo' => 'url', 'rotulo' => 'LinkedIn',
            'ajuda' => 'Endereço completo da página. Vazio, o ícone não aparece.',
            'regras' => ['nullable', 'url:https', 'max:300'],
        ],

        // ---------------------------------------------------------- empresa
        'empresa.descricao' => [
            'grupo' => 'empresa', 'tipo' => 'texto_longo', 'rotulo' => 'Descrição da empresa',
            'ajuda' => 'Uma ou duas frases. Vai para o Google (dados estruturados) e para o llms.txt, que é o que as IAs leem. Até 300 letras.',
            'padrao' => 'Sites com painel próprio e sistemas de gestão para imobiliárias, clínicas, sindicatos, oficinas e artistas, do primeiro esboço ao app no celular.',
            'regras' => ['required', 'string', 'max:300'],
        ],
        'empresa.imagem_og' => [
            'grupo' => 'empresa', 'tipo' => 'imagem', 'rotulo' => 'Imagem de compartilhamento',
            'ajuda' => 'A imagem que aparece quando alguém manda o link do site no WhatsApp, LinkedIn etc. '
                .'O site recorta o centro em 1200×630. Os textos do blog usam a capa de cada um.',
        ],

        // ------------------------------------------------------------- home
        'home.hero_etiqueta' => [
            'grupo' => 'home', 'tipo' => 'texto', 'rotulo' => 'Topo: etiqueta',
            'ajuda' => 'A linha pequena acima do título. Até 50 letras.',
            'padrao' => 'Sites · Sistemas · Aplicativos',
            'regras' => ['required', 'string', 'max:50'],
        ],
        'home.hero_titulo' => [
            'grupo' => 'home', 'tipo' => 'texto', 'rotulo' => 'Topo: título',
            'ajuda' => 'O título grande da home. Ponha entre *asteriscos* o trecho que fica em destaque. Até 60 letras.',
            'padrao' => 'Software que resolve *problemas reais*.',
            'regras' => ['required', 'string', 'max:60'],
            'enfase' => true,
        ],
        'home.hero_texto' => [
            'grupo' => 'home', 'tipo' => 'texto_longo', 'rotulo' => 'Topo: texto',
            'ajuda' => 'A frase embaixo do título. Até 200 letras.',
            'padrao' => 'Sites com painel próprio e sistemas de gestão para imobiliárias, clínicas, sindicatos, oficinas e artistas — do primeiro esboço ao app no celular.',
            'regras' => ['required', 'string', 'max:200'],
        ],
        'home.fazemos_etiqueta' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'O que fazemos: etiqueta',
            'padrao' => 'O que fazemos', 'regras' => ['required', 'string', 'max:40'],
        ],
        'home.fazemos_titulo' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'O que fazemos: título',
            'padrao' => 'Soluções digitais sob medida para o seu negócio.', 'regras' => ['required', 'string', 'max:90'],
        ],
        'home.trabalhos_etiqueta' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Trabalhos: etiqueta',
            'padrao' => 'Trabalhos', 'regras' => ['required', 'string', 'max:40'],
        ],
        'home.trabalhos_titulo' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Trabalhos: título',
            'padrao' => 'Cada tipo de site, com um exemplo que já fizemos.', 'regras' => ['required', 'string', 'max:90'],
        ],
        'home.trabalhos_texto' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Trabalhos: frase',
            'padrao' => 'Um de cada tipo. Os outros estão na página de trabalhos.', 'regras' => ['nullable', 'string', 'max:200'],
        ],
        'home.demonstracoes_etiqueta' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Demonstrações: etiqueta',
            'padrao' => 'Demonstrações', 'regras' => ['required', 'string', 'max:40'],
        ],
        'home.demonstracoes_titulo' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Demonstrações: título',
            'padrao' => 'Veja funcionando antes de pedir.', 'regras' => ['required', 'string', 'max:90'],
        ],
        'home.demonstracoes_texto' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Demonstrações: frase',
            'padrao' => 'Painéis de verdade, com dados inventados. Clique, filtre e imagine com os números do seu negócio.',
            'regras' => ['nullable', 'string', 'max:200'],
        ],
        'home.contato_etiqueta' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Contato: etiqueta',
            'padrao' => 'Contato', 'regras' => ['required', 'string', 'max:40'],
        ],
        'home.contato_titulo' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Contato: título',
            'padrao' => 'Tem um projeto em mente? Vamos conversar.', 'regras' => ['required', 'string', 'max:90'],
        ],
        'home.contato_texto' => [
            'grupo' => 'secoes', 'tipo' => 'texto', 'rotulo' => 'Contato: frase',
            'padrao' => 'Conte pra gente o que você precisa. Preencha o formulário e retornamos o contato.',
            'regras' => ['nullable', 'string', 'max:200'],
        ],
    ];

    /** @return array<string, string|null> tudo, já com os padrões aplicados */
    public static function todas(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        try {
            /** @var array<string, string|null> $gravadas */
            $gravadas = Cache::rememberForever(
                self::CHAVE_CACHE,
                fn () => Configuracao::query()->pluck('valor', 'chave')->all(),
            );
            $memorizar = true;
        } catch (Throwable $e) {
            // Banco fora: o site segue com os padrões. Sem memo nem cache, para
            // a próxima requisição tentar de novo.
            report($e);
            $gravadas = [];
            $memorizar = false;
        }

        $saida = [];
        foreach (self::CATALOGO as $chave => $meta) {
            $valor = $gravadas[$chave] ?? null;
            $saida[$chave] = ($valor === null || $valor === '') ? ($meta['padrao'] ?? null) : $valor;
        }

        if ($memorizar) {
            self::$memo = $saida;
        }

        return $saida;
    }

    public static function valor(string $chave): ?string
    {
        return self::todas()[$chave] ?? null;
    }

    /** As views chamam `$site('contato.email')` (compartilhado no AppServiceProvider). */
    public function __invoke(string $chave): ?string
    {
        return self::valor($chave);
    }

    /** O título com *destaque* vira HTML: escapa tudo e só então troca os asteriscos por <em>. */
    public static function comEnfase(string $chave): string
    {
        $texto = e((string) self::valor($chave));

        return (string) preg_replace('/\*([^*]+)\*/u', '<em>$1</em>', $texto);
    }

    /** "+55 11 97686-2551", derivado do WhatsApp — o telefone não se digita duas vezes. */
    public static function telefone(): string
    {
        $n = (string) self::valor('contato.whatsapp');
        if (! preg_match('/^55(\d{2})(\d{4,5})(\d{4})$/', $n, $m)) {
            return '+'.$n;
        }

        return "+55 {$m[1]} {$m[2]}-{$m[3]}";
    }

    public static function whatsappUrl(?string $mensagem = null): string
    {
        $mensagem ??= self::valor('contato.mensagem_whatsapp');

        return 'https://wa.me/'.self::valor('contato.whatsapp').($mensagem ? '?text='.rawurlencode($mensagem) : '');
    }

    /** URL pública da imagem OG gravada, ou o arquivo de sempre. */
    public static function imagemOg(): string
    {
        $caminho = self::valor('empresa.imagem_og');

        return $caminho ? Storage::disk('public')->url($caminho) : asset('og-urbisdev.png');
    }

    /** @return list<string> perfis preenchidos, para o `sameAs` do JSON-LD */
    public static function perfis(): array
    {
        return array_values(array_filter([
            self::valor('redes.instagram'),
            self::valor('redes.linkedin'),
        ]));
    }

    /** @param array<string, mixed> $valores */
    public static function gravar(array $valores): void
    {
        foreach ($valores as $chave => $valor) {
            if (! array_key_exists($chave, self::CATALOGO) || self::CATALOGO[$chave]['tipo'] === 'imagem') {
                continue; // imagem só entra por `gravarImagem()`
            }

            self::gravarUma($chave, is_string($valor) ? trim($valor) : null);
        }

        self::esquecer();
    }

    /**
     * Grava o caminho de uma imagem já guardada pelo pacote urbanodev/midia e
     * registra o uso. A anterior NÃO é apagada (255, 07/11/2026): sem uso, a
     * faxina do pacote a põe na quarentena.
     */
    public static function gravarImagem(string $chave, ?string $caminho): void
    {
        self::gravarUma($chave, $caminho);
        Configuracao::query()->where('chave', $chave)->first()?->sincronizarMidias('valor', [$caminho]);
        self::esquecer();
    }

    private static function gravarUma(string $chave, ?string $valor): void
    {
        $registro = Configuracao::firstOrNew(['chave' => $chave]);
        $registro->valor = ($valor === null || $valor === '') ? null : $valor;
        $registro->grupo = self::CATALOGO[$chave]['grupo'];
        $registro->save(); // `save()` e não `updateOrCreate`: passa pelo Auditable
    }

    public static function esquecer(): void
    {
        self::$memo = null;
        Cache::forget(self::CHAVE_CACHE);
    }

    /**
     * Regras de validação, derivadas do catálogo.
     *
     * ⚠️ O ponto da chave vai escapado: `valores.contato.email` faria o
     * validador procurar três níveis, a regra não casaria com nada e a tela
     * diria "salvo" sem gravar (pago no Duo de 31/08 a 03/10/2026).
     *
     * @return array<string, array<int, string>>
     */
    public static function regras(): array
    {
        $regras = [];
        foreach (self::CATALOGO as $chave => $meta) {
            if ($meta['tipo'] === 'imagem') {
                continue;
            }
            $lista = $meta['regras'] ?? ['nullable', 'string', 'max:255'];
            // Chave com padrão aceita vazio: vazio quer dizer "volta ao texto de sempre".
            if (isset($meta['padrao'])) {
                $lista = array_map(fn ($r) => $r === 'required' ? 'nullable' : $r, $lista);
            }
            $regras['valores.'.str_replace('.', '\\.', $chave)] = $lista;
        }

        return $regras;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function paraTela(): array
    {
        $valores = self::todas();
        $grupos = [];

        foreach (self::CATALOGO as $chave => $meta) {
            $grupos[$meta['grupo']][] = [
                'chave' => $chave,
                'tipo' => $meta['tipo'],
                'rotulo' => $meta['rotulo'],
                'ajuda' => $meta['ajuda'] ?? null,
                'valor' => $valores[$chave],
                'padrao' => $meta['padrao'] ?? null,
                'max' => self::maximo($meta['regras'] ?? []),
                'url' => $meta['tipo'] === 'imagem' ? self::imagemOg() : null,
            ];
        }

        return $grupos;
    }

    /** @param list<string> $regras */
    private static function maximo(array $regras): ?int
    {
        foreach ($regras as $r) {
            if (str_starts_with($r, 'max:')) {
                return (int) substr($r, 4);
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public static function rotulosDosGrupos(): array
    {
        return [
            'contato' => 'Contato',
            'redes' => 'Redes',
            'empresa' => 'Empresa e compartilhamento',
            'home' => 'Topo da home',
            'secoes' => 'Seções da home',
        ];
    }
}
