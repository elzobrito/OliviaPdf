# Olivia PDF

Fork do [FPDF](http://www.fpdf.org/) com extensões voltadas para geração de PDFs em PHP, incluindo cabeçalho customizado, rodapé com autenticação, tabelas simples, HTML básico e texto rotacionado.

## Requisitos

- PHP `^7.1 || ^8.0`
- Composer

## Instalação

Se o pacote estiver publicado no Composer:

```bash
composer require elzobrito/olivia-pdf
```

Se o projeto estiver sendo usado localmente, basta manter o autoload do Composer ativo:

```php
require __DIR__ . '/vendor/autoload.php';
```

## Namespace

```php
use Oliviapdf\Pdf;
```

## O que este fork adiciona

Além dos recursos originais do FPDF, a classe `Oliviapdf\Pdf` inclui:

- `Circle()` e `Ellipse()` para desenho de formas
- `SetWidths()`, `SetAligns()` e `Row()` para tabelas simples com quebra automática
- `WriteHTML()` com suporte básico a tags HTML
- `TextWithDirection()` e `TextWithRotation()` para texto orientado ou rotacionado
- `Header()` com logo, título e subtítulo
- `Footer()` com paginação e código de autenticação lateral

## Exemplo rápido

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Oliviapdf\Pdf;

$pdf = new Pdf();
$pdf->AliasNbPages();

$pdf->image_logo = __DIR__ . '/logo.png';
$pdf->title = 'Relatório de Atendimento';
$pdf->subtitle = 'Período de abril de 2026';
$pdf->autentication = 'ABC123456';

$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

$pdf->WriteHTML('<p align="center"><b>Olivia PDF</b><br>Fork do FPDF com recursos extras.</p>');
$pdf->Ln(8);

$pdf->SetWidths([50, 80, 50]);
$pdf->SetAligns(['L', 'L', 'C']);
$pdf->Row(['Nome', 'Descrição', 'Status']);
$pdf->Row(['Olivia', 'Documento gerado com tabela automática', 'OK']);
$pdf->Row(['Sistema', 'Linha com quebra automática para textos maiores dentro da célula', 'ATIVO']);

$pdf->Ln(10);
$pdf->Circle(20, 90, 5);
$pdf->TextWithRotation(30, 100, 'Texto rotacionado', 45);

$pdf->Output('I', 'exemplo.pdf');
```

## Cabeçalho e rodapé

O cabeçalho e o rodapé são acionados automaticamente ao usar `AddPage()`.

### Propriedades disponíveis

- `$image_logo`: caminho da imagem exibida no topo
- `$title`: título centralizado no cabeçalho
- `$title_config_array`: configuração da fonte do título no formato `['Arial', 'B', 10]`
- `$subtitle`: subtítulo abaixo do título
- `$subtitle_config_array`: configuração da fonte do subtítulo no formato `['Arial', '', 9]`
- `$autentication`: texto exibido lateralmente no rodapé
- `$end_page`: sufixo da paginação, por padrão `/{nb}`

### Exemplo

```php
$pdf->AliasNbPages();
$pdf->image_logo = __DIR__ . '/logo.png';
$pdf->title = 'Meu Documento';
$pdf->title_config_array = ['Arial', 'B', 12];
$pdf->subtitle = 'Subtítulo do relatório';
$pdf->subtitle_config_array = ['Arial', '', 10];
$pdf->autentication = 'TOKEN-001';
```

## Tabelas com `Row()`

Antes de chamar `Row()`, defina a largura das colunas com `SetWidths()` e, se quiser, o alinhamento com `SetAligns()`.

```php
$pdf->SetFont('Arial', '', 10);
$pdf->SetWidths([40, 100, 40]);
$pdf->SetAligns(['L', 'L', 'C']);

$pdf->Row(['Código', 'Descrição', 'Valor']);
$pdf->Row(['001', 'Item com descrição longa e quebra automática dentro da célula', 'R$ 10,00']);
```

### Observações

- `Row()` usa `MultiCell()` internamente
- a altura da linha é calculada automaticamente
- se faltar espaço na página, ocorre quebra automática
- se nenhuma fonte tiver sido definida com `SetFont()`, a classe lança erro

## HTML suportado em `WriteHTML()`

O parser é propositalmente simples. Ele atende textos rápidos, mas não substitui um renderizador HTML completo.

### Tags suportadas

- `<b>`, `<i>`, `<u>`
- `<a href="...">`
- `<br>`
- `<p align="center">`
- `<hr>`

### Exemplo

```php
$pdf->SetFont('Arial', '', 12);
$pdf->WriteHTML('<b>Negrito</b> <i>itálico</i> <u>sublinhado</u><br>');
$pdf->WriteHTML('<a href="https://example.com">Abrir link</a>');
```

## Métodos extras

### `Circle(float $x, float $y, float $r, string $style = 'D')`

Desenha um círculo.

### `Ellipse(float $x, float $y, float $rx, float $ry, string $style = 'D')`

Desenha uma elipse.

### `TextWithDirection(float $x, float $y, string $txt, string $direction = 'R')`

Escreve texto nas direções:

- `R`: esquerda para direita
- `L`: direita para esquerda
- `U`: de baixo para cima
- `D`: de cima para baixo

### `TextWithRotation(float $x, float $y, string $txt, float $txt_angle, float $font_angle = 0)`

Escreve texto rotacionado em qualquer ângulo.

## Compatibilidade e observações

- A classe continua herdando de `Fpdf\Fpdf`
- O texto é convertido para `windows-1252` internamente para manter compatibilidade com o fluxo tradicional do FPDF
- Para recursos avançados de Unicode, layout complexo ou HTML/CSS completo, pode ser necessário adotar uma biblioteca com suporte mais amplo

## Desenvolvimento

Validações úteis durante manutenção:

```bash
composer validate --no-check-publish
php -l src/Pdf.php
```

## Licença

Este projeto é distribuído sob a licença MIT. O FPDF mantém sua licença própria no pacote original.
