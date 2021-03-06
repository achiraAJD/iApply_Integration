USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebDecisionAppReallocateFile]    Script Date: 2/09/2021 12:45:56 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/*
+--------------------------------------------------------------------------------------------
| FUNCTION:  
| HISTORY:
| DATE			WHO      		DESCRIPTION OF CHANGE
| ---------------------------------------------------------------------------------
| 27/02/2019 	MXSAM			Reallocate a file from the Decision App
+--------------------------------------------------------------------------------------------*/
--grant execute on [spWebDecisionAppReallocateFile] to webuser
ALTER PROCEDURE [dbo].[spWebDecisionAppReallocateFile]
@OBJ_ID numeric(18,0),
@OBJT_ID numeric(18,0),
@AllocTo varchar(16),
@AllocBy varchar(16),
@LGO_Reference varchar(16) = null,
@Comment varchar(max) = '',
@lastApp bit = 1, --added by achira
@GRP_ID numeric(18,0) = null -- added by achira
	
AS

BEGIN
	SET NOCOUNT ON 

	DECLARE @AU_ID_AllocTo numeric (18,0), @AU_ID_AllocBy numeric (18,0), @FA_ID numeric (18,0)
	DECLARE @AU_FullName_To varchar(100), @AU_FullName_From varchar(100), @AU_Email_To varchar(100)
	DECLARE @msg varchar(max) ='', @APP_ApplicNumber numeric(18,0), @AT_Desc varchar(100),@PN_Name varchar(100),@APP_CB_ID_FileStatus numeric(18,0)

	SELECT TOP 1 @AU_ID_AllocTo = AU_ID, @AU_FullName_To = AU_FullName, @AU_Email_To = AU_Email FROM AppUsers WHERE AU_Name = @AllocTo
	SELECT TOP 1 @AU_ID_AllocBy = AU_ID, @AU_FullName_From = AU_FullName FROM AppUsers WHERE AU_Name = @AllocBy
	SELECT @FA_ID = FA_ID FROM FileAllocation WHERE FA_OBJ_ID = @OBJ_ID AND FA_OBJT_ID = @OBJT_ID

	IF @AU_ID_AllocTo IS NULL OR @AU_ID_AllocBy IS NULL RETURN

	
	
	IF @FA_ID IS NULL BEGIN
		DECLARE
			@AS_ID int,
			@FA_CB_ID_Status int
		IF @OBJT_ID = 1
			SELECT @AS_ID = AST_AS_ID FROM Applications INNER JOIN ApplicationStreamTypes ON APP_AST_ID = AST_ID WHERE APP_ID = @OBJ_ID
		ELSE RETURN
		SELECT @FA_CB_ID_Status = CB_ID FROM ComboBoxes INNER JOIN ComboBoxNames ON CB_CBN_ID = CBN_ID WHERE CBN_Name = 'FileAllocStatus' AND CB_Code = 'FAA'
		INSERT INTO FileAllocation (FA_AS_ID, FA_OBJT_ID, FA_OBJ_ID, FA_AU_ID_AllocTo, FA_AU_ID_AllocBy, FA_AllocatedDate, FA_CB_ID_Status, CreationUser, CreationDateTime, LastUpdateUser, LastUpdateDateTime) VALUES
		(@AS_ID, @OBJT_ID, @OBJ_ID, @AU_ID_AllocTo, @AU_ID_AllocBy, GETDATE(), @FA_CB_ID_Status, @AllocBy, GETDATE(), @AllocBy, GETDATE())
	END
	ELSE BEGIN
		INSERT INTO FileAllocationHistory (FAH_FA_ID,FAH_AU_ID_AllocTo,FAH_AU_ID_AllocBy,FAH_AllocatedDate,FAH_CB_ID_Status)
		SELECT FA_ID,FA_AU_ID_AllocTo,FA_AU_ID_AllocBy,FA_AllocatedDate,FA_CB_ID_Status FROM FileAllocation WHERE FA_ID = @FA_ID
		UPDATE FileAllocation set FA_AU_ID_AllocTo = @AU_ID_AllocTo, FA_AU_ID_AllocBy = @AU_ID_AllocBy, FA_AllocatedDate = getdate(), LastUpdateUser = @AllocBy, LastUpdateDateTime = getdate() WHERE FA_ID = @FA_ID
	END
	
	IF @Comment != ''
		exec spWebAddNote @Content = @Comment, @Switch = null, @Param = null, @NT_IsSensitive = 0, @OBJT_Code = 'FA', @NT_OBJ_ID = @FA_ID, @NTS_Code = 'ACT', @NTYP_Code = 'GEN', @AU_Name = @AllocBy, @AU_Name_Allocated = @AllocTo


	IF DB_NAME() != 'LGS' OR CONNECTIONPROPERTY('local_net_address') != '10.106.32.178' BEGIN
		SET @AU_Email_To = 'Achira.Warnakulasuriya@sa.gov.au'--'david.dennis@sa.gov.au'
	END 
	
	--send notification
	IF(@AU_Email_To is not NULL and @AU_Email_To != '' and @lastApp = 1) BEGIN
		DECLARE @OBJ_IDs TABLE (O_ID numeric(18,0)) 
		IF @GRP_ID IS NOT NULL BEGIN
			INSERT INTO @OBJ_IDs (O_ID) select APP_ID from Applications where APP_GRP_ID = @GRP_ID
		END ELSE BEGIN
			INSERT INTO @OBJ_IDs (O_ID) VALUES (@OBJ_ID)
		END
		
		WHILE (select count(*) from @OBJ_IDs) > 0 BEGIN
			SELECT TOP 1 @OBJ_ID = O_ID FROM @OBJ_IDs ORDER BY O_ID ASC
			DELETE FROM @OBJ_IDs WHERE O_ID = @OBJ_ID
			

			SELECT @APP_ApplicNumber = vwApplicationData.APP_ApplicNumber, @AT_Desc = AT_Desc, @PN_Name = PN_Name, @APP_CB_ID_FileStatus = APP_CB_ID_FileStatus 
			from vwApplicationData 
			inner join Applications on vwApplicationData.APP_ID = Applications.APP_ID
			where vwApplicationData.APP_ID = @OBJ_ID
			
			IF @LGO_Reference IS NULL
				SELECT @LGO_Reference = JSON_VALUE(LSA_JSON, '$.info.LGO_Reference') FROM LicenceSystemApplications WHERE LSA_APP_ID = @OBJ_ID

			SET @msg =  @msg +'<table>'
			SET @msg =  @msg +'<tr><td style="padding-right:10px"><strong>LGO Reference</strong></td><td>'+ISNULL(@LGO_Reference, '-')+'</td></tr>'
			SET @msg =  @msg +'<tr><td style="padding-right:10px"><strong>Application Number</strong></td><td>'+CAST(@APP_ApplicNumber AS varchar(10))+'</td></tr>'
			SET @msg =  @msg +'<tr><td style="padding-right:10px"><strong>Premises Name</strong></td><td>'+@PN_Name+'</td></tr>'
			IF(@APP_CB_ID_FileStatus is not null)
				SET @msg =  @msg +'<tr><td style="padding-right:10px"><strong>File Status</strong></td><td>'+(SELECT CB_Description from ComboBoxes where CB_ID = @APP_CB_ID_FileStatus)+'</td></tr>'
			SET @msg =  @msg +'<tr><td style="padding-right:10px"><strong>Application Type</strong></td><td>'+@AT_Desc+'</td></tr>'
			SET @msg =  @msg +'<tr><td style="padding-right:10px"><strong>Allocated by</strong></td><td>'+@AU_FullName_From+'</td></tr>'
			IF @Comment != ''
				SET @msg =  @msg +'<tr><td style="padding-right:10px"><strong>Comment</strong></td><td>'+@Comment+'</td></tr>'
			SET @msg =  @msg +'</table>'
			SET @msg = @msg + '<br>' --achira added
		
		END

		DECLARE @tmpNewValue TABLE ([Success] varchar(10))
		
		INSERT INTO @tmpNewValue 
		exec spWebSendNotification @Type = 'FileAssignment', @ToAddress = @AU_Email_To, @Subject = 'You have been allocated a file', @Content = @msg;
		--print @msg --added by achira to test/check email content
	END
	
	SELECT @AU_ID_AllocTo AU_ID_AllocTo

END

