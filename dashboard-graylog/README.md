# Sistema de Gestão de Licenças e Monitorização de Syslog

![Versão](https://img.shields.io/badge/versão-0.1%20(Alfa)-blue)
![Estado](https://img.shields.io/badge/estado-Em%20Desenvolvimento-orange)

Sistema centralizado desenvolvido para a Câmara Municipal de Oliveira do Hospital. A plataforma permite a recolha, normalização e monitorização de logs de segurança (Syslog) de ambientes Windows, integrando também um módulo completo para a gestão de licenças de software e departamentos.

## 🏗️ Estrutura e Arquitetura

O projeto adota uma arquitetura baseada em serviços, orquestrada através de Docker, dividindo-se em dois componentes fundamentais:

* **[Go Listener](./dashboard-graylog/go-listener):** Agente desenvolvido em Go responsável por intercetar os eventos de segurança do Windows, normalizar as informações e registá-las de forma estruturada na base de dados.
* **[Laravel Panel](./dashboard-graylog/laravel-panel):** Dashboard administrativo desenvolvido em PHP (Laravel + Filament). Fornece uma interface gráfica rica (SPA) para visualização de estatísticas dos logs, gestão de utilizadores, departamentos e controlo de licenças ativas.

## ⚙️ Pré-requisitos

Para correr o projeto em ambiente de desenvolvimento local, é necessário ter instalado:
* [Docker](https://www.docker.com/) e Docker Compose
* [Git](https://git-scm.com/)

## 🚀 Instalação e Execução

1.  Clona o repositório para a tua máquina local:
    ```bash
    git clone [https://github.com/teu-utilizador/projetologs.git](https://github.com/teu-utilizador/projetologs.git)
    cd projetologs
    ```

2.  Navega para a diretoria principal onde se encontra a configuração dos contentores:
    ```bash
    cd dashboard-graylog
    ```

3.  Inicia a infraestrutura através do Docker Compose:
    ```bash
    docker compose up -d
    ```

*(Nota: Consultar os ficheiros README internos de cada componente para configurações específicas de ambiente, como os ficheiros `.env` e a execução das migrações da base de dados).*

## 🏷️ Versionamento

O projeto utiliza tags Git para o controlo de versões. A versão atual é a **0.1 (Alfa)**, que representa a implementação inicial da infraestrutura base, a integração Go-Laravel e o desenho das interfaces administrativas.