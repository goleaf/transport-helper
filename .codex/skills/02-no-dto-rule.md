# No DTO Rule

DTOs are forbidden.

Do not create:

* app/Data
* *DTO.php
* *Dto.php
* Spatie Data classes
* DataTransferObject classes

Allowed:

* arrays
* Eloquent models
* FormRequest validated arrays
* Laravel Validator
* Services
* JSON columns
* Enums
* PHPDoc array shapes

Allowed example:

```php
/**
 * @param array<string,mixed> $input
 * @return array<string,mixed>
 */
public function calculate(array $input): array
```

Forbidden example:

```php
public function calculate(CalculationInputDTO $input): CalculationResultDTO
```

Before commit:

* run ./scripts/check-no-dto.sh
