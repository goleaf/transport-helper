# No DTO Rule

DTOs are forbidden in this project.

Do not create:
- DTO classes;
- app/Data;
- Spatie Data;
- classes ending with DTO;
- CalculationInputDTO;
- CalculationResultDTO;
- EmailFormAutofillDTO;
- SupplierConfirmationDTO;
- CarrierQuoteDTO;
- TransportQuoteDTO;
- LogisticsUpdateDTO.

Use instead:
- associative arrays;
- Eloquent models;
- FormRequest validated arrays;
- Laravel Validator;
- JSON columns;
- PHPDoc array shapes;
- Enums;
- Services.

Allowed method style:

/**
 * @param array<string,mixed> $input
 * @return array<string,mixed>
 */
public function calculate(array $input): array

Allowed service style:

/**
 * @param EmailMessage $email
 * @param FormTemplate $template
 * @param array<string,mixed> $options
 * @return array<string,mixed>
 */
public function createAutofillRun(
    EmailMessage $email,
    FormTemplate $template,
    array $options = []
): array

Forbidden:
public function calculate(CalculationInputDTO $input): CalculationResultDTO

Before every commit, search for forbidden DTOs:
- app/Data directory must not exist;
- no class names ending with DTO should be introduced by this project.
