USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebDecisionAppAddLSA]    Script Date: 1/07/2022 11:50:02 AM ******/
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
| 25/09/2019 	NIDDD						Created 
+--------------------------------------------------------------------------------------------*/

ALTER PROCEDURE [dbo].[spWebDecisionAppAddLSA]
	@LSA_LIC_ID numeric(18),
	@LSA_JSON varchar(max),
	@LSA_APP_ID numeric(18),
	@iApply bit = 0
AS

SET NOCOUNT ON;
DECLARE @LSA_ID numeric(18,0), @V_AT_Desc NVARCHAR(4000)
BEGIN
	IF @iApply = 1 BEGIN
		Select @V_AT_Desc = AT_Desc from ApplicationTypes 
		where AT_ID = (SELECT JSON_VALUE(@LSA_JSON,'$.info.AT_ID'));

		Select  @LSA_JSON = JSON_MODIFY(@LSA_JSON,'$.info.AT_Desc', @V_AT_Desc) ;
		--Select  @LSA_JSON

	END
	SELECT @LSA_ID = LSA_ID FROM LicenceSystemApplications where LSA_APP_ID = @LSA_APP_ID
	IF @LSA_ID IS NOT NULL
	BEGIN
		select 1 as LSA_ID --LSA already exists
		RETURN
	END

	INSERT INTO LicenceSystemApplications (LSA_LIC_ID, LSA_APP_ID, LSA_JSON, CreationDateTime, CreationUser, LastUpdateDateTime, LastUpdateUser)
	values (@LSA_LIC_ID, @LSA_APP_ID, @LSA_JSON, getdate(), 'SYSTM', getdate(), 'SYSTM')

	select SCOPE_IDENTITY() as LSA_ID
END


--grant execute on [spWebDecisionAppAddLSA] to webuser

