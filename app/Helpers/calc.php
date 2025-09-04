// submitLoanCalc
    public function submitLoanCalc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_amount' => 'required|numeric',
            'loan_duration' => 'required|numeric',
        ]);

        // translate error messages to arabic
        $validator->setCustomMessages([
            'loan_amount.required' => 'حجم القرض مطلوب',
            'loan_amount.numeric' => 'حجم القرض يجب ان يكون رقم',
            'loan_duration.required' => 'مدة القرض مطلوبة',
            'loan_duration.numeric' => 'مدة القرض يجب ان تكون رقم',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ]);
        }

        $amount = $request->loan_amount;
        $duration = $request->loan_duration;

        $loanPrograms =  LoanProgram::whereRaw('CAST(amount_from AS UNSIGNED) <= ?', [$amount])
            ->whereRaw('CAST(amount_to AS UNSIGNED) >= ?', [$amount])
            ->where('loan_duration_from', '<=', $duration)
            ->where('loan_duration_to', '>=', $duration);

        if ($loanPrograms->count() == 0){
            $loanProgramAva = LoanProgram::whereRaw('CAST(amount_from AS UNSIGNED) <= ?', [$amount])
            ->whereRaw('CAST(amount_to AS UNSIGNED) >= ?', [$amount]);

            $suggestedPrograms = LoanProgram::whereRaw('CAST(amount_from AS UNSIGNED) <= ?', [$amount])
            ->orderBy('amount_to', 'desc')
            ->limit(1)
            ->get();

            $loanPrograms = $suggestedPrograms;
            foreach ($loanPrograms as $loanProgram) {
                $amount = $loanProgram->amount_to;
                $duration = $loanProgram->loan_duration_from;
                $loanProgram->total_interst = ($amount * $duration * ( $loanProgram->percentage_interest_rate / 12 )) / 100;
                $loanProgram->total_administrative_expenses = ($amount * $loanProgram->administrative_expenses) / 100;
                $loanProgram->loan_amount = $amount + $loanProgram->total_interst;
                $loanProgram->installment = number_format(($loanProgram->loan_amount / $duration), 2);
                // Amount disbursed
                $loanProgram->amount_disbursed = $amount - $loanProgram->total_administrative_expenses;
                $loanProgram->amount = $amount;
                $loanProgram->duration = $duration;
            }

            if ($loanProgramAva->count() != 0){
                return response()->json([
                    'status' => true,
                    'errors' => [' المده المحددة غير متاحة لهذا المبلغ - يرجى اختيار مدة من '
                    . $loanProgramAva->first()->loan_duration_from . ' الى ' . $loanProgramAva->first()->loan_duration_to . ' شهر.'],

                    'data' => $loanPrograms,
                    'amount' => $amount,
                    'duration' => $duration
                ]);
            }

            return response()->json([
                'status' => true,
                'errors' => ['لا يوجد برنامج قرض متاح لهذا المبلغ والمدة المحددة إليك أحد البرامج المقترحة الأقرب إلى طلبك'],
                'data' => $loanPrograms,
                'amount' => $amount,
                'duration' => $duration
            ]);
        }

        $loanPrograms = $loanPrograms->get();
        foreach ($loanPrograms as $loanProgram) {
            $loanProgram->total_interst = ($amount * $duration * ( $loanProgram->percentage_interest_rate / 12 )) / 100;
            $loanProgram->total_administrative_expenses = ($amount * $loanProgram->administrative_expenses) / 100;
            $loanProgram->loan_amount = $amount + $loanProgram->total_interst;
            $loanProgram->installment = number_format(($loanProgram->loan_amount / $duration), 2);
            // Amount disbursed
            $loanProgram->amount_disbursed = $amount - $loanProgram->total_administrative_expenses;
            $loanProgram->amount = $amount;
            $loanProgram->duration = $duration;
        }

        return response()->json([
            'status' => true,
            'errors' => [],
            'data' => $loanPrograms,
            'amount' => $amount,
            'duration' => $duration
        ]);
    }
