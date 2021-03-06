USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebDecisionAppOrderFinaliseDraft]    Script Date: 3/06/2022 2:26:18 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO


/*
+--------------------------------------------------------------------------------------------
| FUNCTION:  
| HISTORY:
| DATE			WHO      					DESCRIPTION OF CHANGE
| ---------------------------------------------------------------------------------
| 01/09/2016 	D Dennis					Created 
| 24/09/2018	D Dennis					Upgraded to Decision App
+--------------------------------------------------------------------------------------------*/

ALTER PROCEDURE [dbo].[spWebDecisionAppOrderFinaliseDraft]
	@APP_IDs varchar(max),
	@AU_Name varchar(50),
	@AOR_Date datetime,
	@AOR_ResultDesc varchar(30),
	@AOR_EffectiveDate datetime,
	@AOR_Notes varchar(max),
	@AOR_AU_ID_Delegate numeric(18,0),
	@AOR_AdjournDate datetime = null,
	@AOR_AdjournType char(1) = null,
	@AOR_AdjournAuthority char(1) = null,
    @AOR_UndertakingEndDate datetime = null,
	@AOR_UndertakingAuthority char(1) = null
AS

DECLARE @OT_Code varchar(5)
DECLARE @APPs table (ID numeric(18,0), AS_Code varchar(5), AOR_OrdNo varchar(16))
DECLARE @AOR_ResultCode varchar(5)
DECLARE @OrderPrefix varchar(1)
DECLARE @Now datetime
SET NOCOUNT ON;

BEGIN
	--get the result code 
	Select top 1 @AOR_ResultCode = AOR_ResultCode from ApplicationOrders where AOR_ResultDesc = @AOR_ResultDesc


	--get the next order number and increment 
	-- insert APP_IDs into the @APPs table
	INSERT INTO @APPs (ID, AS_Code, AOR_OrdNo)
	SELECT value, null, null FROM OPENJSON(@APP_IDs)

	-- add AS_Code to the @APPS table
	UPDATE @APPs SET AS_Code = ApplicationStreams.AS_Code
	FROM Applications
	INNER JOIN ApplicationStreamTypes ON APP_AST_ID = AST_ID
	INNER JOIN ApplicationStreams ON AST_AS_ID = AS_ID
	WHERE APP_ID = ID

	-- add AOR_OrdNo to the @APPS table
	DECLARE MY_CURSOR CURSOR LOCAL STATIC READ_ONLY FORWARD_ONLY FOR 
	select ID, AS_Code, AOR_OrdNo from @APPs
	DECLARE @ID numeric(18,0), @AS_Code varchar(5), @AOR_OrdNo varchar(16)

	OPEN MY_CURSOR
	FETCH NEXT FROM MY_CURSOR INTO @ID, @AS_Code, @AOR_OrdNo
	WHILE @@FETCH_STATUS = 0
	BEGIN 
		IF (SELECT COUNT(*) FROM @APPs WHERE AS_Code = @AS_Code AND AOR_OrdNo IS NULL) > 0 BEGIN
			If @AS_CODE = 'L' BEGIN
				SET  @OT_Code = 'LCO'
				SET @OrderPrefix = 'B' 
			END
			Else if @AS_CODE = 'G' BEGIN
				SET  @OT_Code = 'GMO'
				SET @OrderPrefix = 'G'
			END
			Else if @AS_CODE = 'C' BEGIN
				SET  @OT_Code ='CAS'
				SET @OrderPrefix = 'S'
			END
			Else if @AS_CODE = 'W' BEGIN
				SET  @OT_Code ='WAG'
				SET @OrderPrefix = 'W'
			END
			Else if @AS_CODE = 'LL' BEGIN
				SET  @OT_Code = null
				SET @OrderPrefix = 'L'
			END
			-- calculate next order number (per stream)
			SET TRANSACTION ISOLATION LEVEL REPEATABLE READ
			BEGIN TRANSACTION 
				Declare @OrderTypeNextNumber as BIGINT
				Declare @OT_ID as NUMERIC(18,0)
				Declare @LV_ID as NUMERIC(18,0)
				set nocount on
				IF @OT_Code IS NOT NULL BEGIN
					-- Get the next number
					select @OrderTypeNextNumber = OT_NextNumber, @OT_ID = OT_ID
					from OrderTypes where OT_Code = @OT_Code
					-- increment the next number
					update OrderTypes set OT_NextNumber = OT_NextNumber + 1, LastUpdateDateTime=getDate(), LastUpdateUser=user  where OT_ID = @OT_ID
					-- update @APPs
					UPDATE @APPs SET AOR_OrdNo =  @OrderPrefix + CAST(@OrderTypeNextNumber as varchar(16)) WHERE AS_Code = @AS_Code
				END ELSE BEGIN
					select @OrderTypeNextNumber = LV_Value, @LV_ID = LV_ID from LookupValues
					inner join Lookups on LV_LOO_ID = LOO_ID
					where LOO_Code = 'NextLLOrderNumber'
					-- increment the next number
					update LookupValues set LV_Value = LV_Value + 1, LastUpdateDateTime=getDate(), LastUpdateUser=user where LV_ID = @LV_ID
					-- update @APPs
					UPDATE @APPs SET AOR_OrdNo =  @OrderPrefix + CAST(@OrderTypeNextNumber as varchar(16)) WHERE AS_Code = @AS_Code
				END
			COMMIT TRANSACTION
		END

		FETCH NEXT FROM MY_CURSOR INTO @ID, @AS_Code, @AOR_OrdNo
	END
	CLOSE MY_CURSOR
	DEALLOCATE MY_CURSOR

	SET @Now = getdate()
	--insert the record
	insert ApplicationOrders (AOR_APP_ID,AOR_OrdNo,AOR_DATE,AOR_ResultDesc,
		AOR_EffectiveDate,
		AOR_ResultCode, LastUpdateDateTime,
		LastUpdateUser,CreationDateTime,CreationUser,AOR_DecisionNotes, AOR_AU_ID_Delegate, AOR_DecisionUser,
		AOR_AdjournDate,AOR_AdjournType,AOR_AdjournAuthority,
		AOR_UndertakingEndDate,AOR_UndertakingAuthority)
	select ID,AOR_OrdNo,@AOR_Date,@AOR_ResultDesc,
		@AOR_EffectiveDate,
		@AOR_ResultCode, @Now,
		'OOS',@Now,'OOS',@AOR_Notes,@AOR_AU_ID_Delegate, @AU_Name,
		@AOR_AdjournDate,@AOR_AdjournType,@AOR_AdjournAuthority,
		@AOR_UndertakingEndDate,@AOR_UndertakingAuthority
		from @APPs
	-- return order no to caller
      
	select AOR_ID, AOR_OrdNo, AOR_APP_ID from ApplicationOrders where CreationDateTime = @Now and AOR_APP_ID in (select ID from @APPs)
END


--grant execute on [spWebDecisionAppOrderFinaliseDraft] to webuser