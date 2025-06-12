<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class ExcelUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('excelFile', FileType::class, [
                'label' => 'Upload Excel File (.xls)',
                'constraints' => [
                    new File([
                        'maxSize' => '10m',
                        'mimeTypes' => [
                            'application/vnd.ms-excel', // .xls
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'text/csv', // .csv
                        ],
                        'mimeTypesMessage' => 'Please upload a valid Excel (.xls, .xlsx) or CSV file',
                    ])
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Import']);
    }
}