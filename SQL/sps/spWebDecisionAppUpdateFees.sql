USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebDecisionAppUpdateFees]    Script Date: 3/06/2022 2:27:30 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/*
+--------------------------------------------------------------------------------------------

| OBJECT: 	spWebDecisionAppUpdateFees
| PURPOSE:  Add a Fee reduction at 50%
+--------------------------------------------------------------------------------------------
| DATE			WHO      		DESCRIPTION OF CHANGE
| ----------- 	--------------- -------------------------------------------------------
| 29/04/2021	D Dennis		Created SP
+--------------------------------------------------------------------------------------------*/
--grant execute on spWebDecisionAppUpdateFees to webuser

--exec spWebDecisionAppUpdateFees @LIC_ID = 6, @AOR_ID = 536336, @AOR_OrdNo = 'B226463';

ALTER PROCEDURE [dbo].[spWebDecisionAppUpdateFees]
@Switch varchar(16) = NULL,
@LIC_ID numeric(18,0),
@AOR_ID numeric(18,0),
@AOR_OrdNo varchar(12)

AS

BEGIN
   
	SET xact_abort ON
	SET ROWCOUNT 0

	DECLARE @tmpLFE table (	
		[LFE_ID] [numeric](18, 0),
		[CreationDateTime] [datetime] NOT NULL,
		[CreationUser] [varchar](50) NOT NULL,
		[LastUpdateDateTime] [datetime] NOT NULL,
		[LastUpdateUser] [varchar](50) NOT NULL,
		[RowVersion] [int] NOT NULL,
		[LFE_CB_ID_FeeType] [numeric](18, 0) NULL,
		[LFE_Exempt] [bit] NOT NULL,
		[LFE_ExemptFinYear] [char](9) NULL,
		[LFE_AOR_ID] [numeric](18, 0) NOT NULL,
		[LFE_DateFrom] [date] NOT NULL,
		[LFE_DateTo] [date] NULL,
		[LFE_CB_ID_ExemptFeeType] [numeric](18, 0) NULL,
		[LFE_RedGranted] [bit] NULL,
		[LFE_RedStartFinYear] [char](9) NULL,
		[LFE_RedEndFinYear] [char](9) NULL,
		[LFE_RedDiscountPerc] [varchar](5) NULL,
		[LFE_RedOrderNo] [varchar](20) NULL
	)

	DECLARE @tmpLFL table (
		[LFL_ID] [numeric](18, 0)
	)

	DECLARE @LFE_ID numeric(18,0)

	--get most furrent fee record in LOGIC for this licence
	INSERT INTO @tmpLFE 
	SELECT TOP 1 [LFE_ID],LicenceFees.CreationDateTime,LicenceFees.CreationUser,LicenceFees.LastUpdateDateTime,LicenceFees.LastUpdateUser,RowVersion,LFE_CB_ID_FeeType,LFE_Exempt,LFE_ExemptFinYear,LFE_AOR_ID,LFE_DateFrom,LFE_DateTo,LFE_CB_ID_ExemptFeeType,LFE_RedGranted,LFE_RedStartFinYear,LFE_RedEndFinYear,LFE_RedDiscountPerc,LFE_RedOrderNo
	FROM LicenceFees
	inner join ApplicationOrders on LFE_AOR_ID = AOR_ID
	inner join Applications on AOR_APP_ID = APP_ID
	inner join vwLIcenceAll on APP_LIC_ID = LIC_ID
	WHERE LIC_ID = @LIC_ID
	ORDER BY LFE_ID DESC

	--Get LFF records assopciated with the record above
	INSERT INTO @tmpLFL
	SELECT LFL_ID from LicenceFeeLoadings where LFL_LFE_ID in (SELECT LFE_ID from @tmpLFE)

	--End date existing LFE record
	UPDATE LicenceFees set LFE_DateTo = CONVERT(VARCHAR(10),  DATEADD(day,-1,getdate()), 111)
	where LFE_ID = (SELECT LFE_ID from @tmpLFE)

	--Insert new LFE record
	INSERT INTO LicenceFees (CreationDateTime,CreationUser,LastUpdateDateTime,LastUpdateUser,RowVersion,LFE_CB_ID_FeeType,LFE_Exempt,LFE_ExemptFinYear,LFE_AOR_ID,LFE_DateFrom,LFE_DateTo,LFE_CB_ID_ExemptFeeType,LFE_RedGranted,LFE_RedStartFinYear,LFE_RedEndFinYear,LFE_RedDiscountPerc,LFE_RedOrderNo)
	SELECT GETDATE(),'iApply',GETDATE(),'iApply',1,LFE_CB_ID_FeeType,LFE_Exempt,LFE_ExemptFinYear,@AOR_ID,CONVERT(VARCHAR(10), getdate(), 111),NULL,LFE_CB_ID_ExemptFeeType,1,'2020/2021','2021/2022',50,@AOR_OrdNo from @tmpLFE

	select @LFE_ID = SCOPE_IDENTITY()

	--Copy old LFL records over to the newly inserted LFE - What a system!
	INSERT INTO LicenceFeeLoadings (CreationDateTime,CreationUser,LastUpdateDateTime,LastUpdateUser,RowVersion,LFL_CB_ID_FeeType,LFL_LFE_ID) 
	SELECT GETDATE(),'iApply',GETDATE(),'iApply',1,LFL_CB_ID_FeeType,@LFE_ID from LicenceFeeLoadings where LFL_LFE_ID = (SELECT LFE_ID from @tmpLFE)

	--Return LFE_ID
	select  @LFE_ID as LFE_ID, success = 1
END
