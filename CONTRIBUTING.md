# Contributing

Thank you for your interest in contributing to Modular Backend Reference.

This project aims to demonstrate practical backend architecture principles, Domain-Driven Design, and maintainable software engineering practices.

Contributions should prioritise architectural consistency, code quality, testability, and clear documentation.

## Development Environment

The project currently targets the following technologies:

- PHP 8.4+
- Laravel
- PostgreSQL
- Redis
- Docker
- Composer
- Pest
- PHPStan

Refer to the project README for installation instructions.

## Development Principles

Contributions should follow these principles:

1. Keep business logic independent of infrastructure.
2. Respect module boundaries and avoid unnecessary coupling.
3. Prefer explicit interfaces and contracts over hidden dependencies.
4. Write automated tests for business behaviour.
5. Document significant architectural decisions.
6. Avoid introducing abstractions without a clear use case.
7. Prefer maintainability over premature optimisation.

## Branching Strategy

Use descriptive branch names based on the type of work.

Examples:

- `feature/inventory-transfer`
- `feature/domain-events`
- `bugfix/inventory-capacity`
- `refactor/shared-domain`
- `docs/architecture-overview`

The `main` branch should always represent a stable, working version of the project.

## Commit Conventions

Use conventional commit messages.

Format:

`type(scope): description`

Examples:

- `feat(inventory): implement item transfer`
- `fix(inventory): prevent negative item quantities`
- `test(inventory): add transfer integration tests`
- `refactor(domain): simplify aggregate root`
- `docs(architecture): document module boundaries`
- `chore(dependencies): update Laravel dependencies`

Keep commits focused on a single logical change.

## Coding Standards

All contributions should:

- Follow PSR-12 coding standards.
- Use strict typing where appropriate.
- Use descriptive class, method, and variable names.
- Prefer immutable value objects where appropriate.
- Avoid unnecessary inheritance and generic abstractions.
- Keep methods focused on a single responsibility.
- Avoid framework dependencies within the Domain layer.

Laravel-specific conventions should be followed within the Infrastructure and Presentation layers where appropriate.

## Architecture Guidelines

The application follows a modular monolith architecture.

Each module should maintain clear separation between:

- Domain
- Application
- Infrastructure
- Presentation

Modules must not directly access another module's internal repositories, entities, or infrastructure implementations.

Cross-module communication should use explicitly defined contracts, application interfaces, or integration events.

Any proposed changes to module boundaries or architectural dependencies should be documented and reviewed.

## Testing Requirements

New functionality should include appropriate automated tests.

Testing should be considered at multiple levels:

- Unit tests for domain behaviour.
- Integration tests for persistence and infrastructure.
- Feature tests for externally exposed functionality.
- Architecture tests for module boundaries and dependency rules.

All relevant tests should pass before a change is considered complete.

## Pull Request Guidelines

Pull requests should include:

1. A clear description of the change.
2. The problem being addressed.
3. An explanation of the implementation.
4. Relevant architectural considerations.
5. Tests added or updated.
6. Documentation changes where appropriate.

Significant architectural changes should include an Architecture Decision Record (ADR).

## Documentation

Documentation is considered part of the implementation.

Changes affecting architecture, module responsibilities, public contracts, or operational behaviour should include corresponding documentation updates.

Architectural decisions should be recorded in `docs/adr/`.

## Questions and Discussions

For questions, suggestions, or proposed improvements, open a GitHub issue describing the problem and the intended outcome.

Constructive discussion of alternative approaches is encouraged.

## Licence

By contributing to this project, you agree that your contributions will be distributed under the MIT Licence.
