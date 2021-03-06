USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddOnlineFinancialTransaction]    Script Date: 1/07/2022 11:55:34 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		David Dennis
-- Create date: 13/04/2017
-- Description:	Add the financial trancaction for online liquor applications
-- =============================================
--spWebAddOnlineFinancialTransaction @FT_TransactionNumber = 123456, @FT_ReceiptNumber = 654321, @FT_AmountPaid = 500.00, @FTFI_Items = '[{"LFF_Type":"FTNAL","FTFI_ProductCode":"L-FTNAL","Amount":"500.00"}]';

ALTER PROCEDURE [dbo].[spWebAddOnlineFinancialTransaction]
	-- Add the parameters for the stored procedure here
	@FT_TransactionNumber numeric(18,0),
	@FT_ReceiptNumber numeric(18,0),
	@FT_AmountPaid numeric(18,2),
	@FTFI_Items varchar(max) = NULL
AS
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;
	
	--Financial transaction insertion
	DECLARE @TranTypeID NUMERIC(18,0), 
		@ExSys NUMERIC(18,0), 
		@Today DATETIME, 
		@Identity NUMERIC(18,0), 
		@AppTypeID NUMERIC(18,0),
		@LFF_ID NUMERIC(18,0)

	SELECT @TranTypeID = CB_ID FROM
		ComboBoxNames INNER JOIN ComboBoxes ON CBN_ID = CB_CBN_ID
		WHERE CBN_Name = 'TransactionType' AND CB_Code = 'TTBPO'
		
	SELECT @ExSys = CB_ID FROM
		ComboBoxNames INNER JOIN ComboBoxes ON CBN_ID = CB_CBN_ID
		WHERE CBN_Name = 'ExternalSystem' AND CB_Code = 'EXSBPO'
		
	SELECT @Today = GETDATE()

	INSERT INTO FinancialTransaction
			(
				FT_TransactionNumber,
				FT_CB_ID_TransType,
				FT_CB_ID_ExtSystem,
				FT_SequenceNumber,
				FT_ReceiptNumber,
				FT_AmountPaid,
				FT_DatePaid,
				CreationDateTime,
				CreationUser,
				LastUpdateDateTime,
				LastUpdateUser,
				[RowVersion]
			)
	VALUES( @FT_TransactionNumber,
		@TranTypeID,
		@ExSys,
		1,
		@FT_ReceiptNumber,
		@FT_AmountPaid,
		@Today,
		@Today,
		'WebUser',
		@Today,
		'WebUser',
		1)
	
	SELECT @Identity = SCOPE_IDENTITY()
	
	--create the relationship to the fee items
	IF @FTFI_Items IS NOT NULL
	BEGIN

		DECLARE @maximum DECIMAL(18,0)
		SELECT @maximum = max(parent_ID) FROM parseJSON(@FTFI_Items)

		INSERT INTO FinancialTransactionFeeItem
		(		
			FTFI_Amount,
			FTFI_FT_ID,
			FTFI_LFF_ID,
			FTFI_ProductCode,
			FTFI_Quantity
		)
		SELECT Amount, @Identity, (SELECT TOP 1 LFF_ID FROM LicenceFeeFramework WHERE LFF_CB_ID_FeeType = (SELECT CB_ID FROM ComboBoxNames INNER JOIN ComboBoxes ON CB_CBN_ID = CBN_ID WHERE CBN_Name = 'FeeType' AND CB_Code = LFF_Type) order by LFF_ID desc), FTFI_ProductCode, 1
		FROM (SELECT parent_ID, Name, StringValue FROM parseJSON(@FTFI_Items) where parent_ID < @maximum) d
		PIVOT (MAX(StringValue) FOR Name in (LFF_Type, FTFI_ProductCode, Amount)) piv
	END

	SELECT @Identity as FT_ID
	
END

